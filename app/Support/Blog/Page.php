<?php

namespace App\Support\Blog;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\StringContainerHelper;

/**
 * A rendered Markdown page: its HTML, the section headings for an
 * "On this page" list, and an estimated reading time.
 */
final class Page
{
    /**
     * @param  array<int, array{id: string, text: string}>  $headings
     */
    public function __construct(
        public readonly string $html,
        public readonly array $headings,
        public readonly int $minutes,
    ) {}

    public static function fromFile(string $path): self
    {
        $result = self::converter()->convert(file_get_contents($path));

        $headings = [];

        foreach ($result->getDocument()->iterator() as $node) {
            if (! $node instanceof Heading || $node->getLevel() !== 2) {
                continue;
            }

            $permalink = $node->firstChild();

            if ($permalink instanceof HeadingPermalink) {
                $headings[] = [
                    'id' => $permalink->getSlug(),
                    'text' => StringContainerHelper::getChildText($node, [HeadingPermalink::class]),
                ];
            }
        }

        $html = $result->getContent();
        $words = str_word_count(strip_tags($html));

        return new self($html, $headings, max(1, (int) ceil($words / 200)));
    }

    private static function converter(): MarkdownConverter
    {
        static $converter;

        if ($converter) {
            return $converter;
        }

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'insert' => 'before',
                'apply_id_to_heading' => true,
                'id_prefix' => '',
                'fragment_prefix' => '',
                'html_class' => 'heading-anchor',
                'title' => 'Link to this section',
                'symbol' => '#',
            ],
            'external_link' => [
                'internal_hosts' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost',
                'open_in_new_window' => true,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new ExternalLinkExtension);

        return $converter = new MarkdownConverter($environment);
    }
}
