<?php

namespace App\Support\Blog;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * A multi-page tutorial defined in config/blog.php, with its Markdown
 * content in resources/content/blog/{slug}.
 */
final class Guide
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<string, string>  $chapters  slug => title, in reading order
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $summary,
        public readonly CarbonImmutable $published,
        public readonly array $tags,
        public readonly string $introduction,
        public readonly array $chapters,
    ) {}

    /**
     * @return Collection<int, self>
     */
    public static function all(): Collection
    {
        return collect(config('blog.guides', []))
            ->map(fn (array $guide, string $slug) => new self(
                slug: $slug,
                title: $guide['title'],
                summary: $guide['summary'],
                published: CarbonImmutable::parse($guide['published']),
                tags: $guide['tags'] ?? [],
                introduction: $guide['introduction'] ?? 'Introduction',
                chapters: $guide['chapters'] ?? [],
            ))
            ->sortByDesc(fn (self $guide) => $guide->published)
            ->values();
    }

    public static function find(string $slug): ?self
    {
        return self::all()->firstWhere('slug', $slug);
    }

    public function hasChapter(string $chapter): bool
    {
        return array_key_exists($chapter, $this->chapters);
    }

    /**
     * 1-based position of a chapter, or 0 for the guide's landing page.
     */
    public function position(?string $chapter): int
    {
        $index = array_search($chapter, array_keys($this->chapters), true);

        return $index === false ? 0 : $index + 1;
    }

    /**
     * The chapter before the given one; null means the landing page.
     */
    public function previous(?string $chapter): ?string
    {
        $position = $this->position($chapter);

        return $position > 1 ? array_keys($this->chapters)[$position - 2] : null;
    }

    public function next(?string $chapter): ?string
    {
        return array_keys($this->chapters)[$this->position($chapter)] ?? null;
    }

    /**
     * Render the landing page (null) or a chapter.
     */
    public function page(?string $chapter = null): Page
    {
        return Page::fromFile(resource_path("content/blog/{$this->slug}/".($chapter ?? 'index').'.md'));
    }

    public function readingMinutes(): int
    {
        return collect([null, ...array_keys($this->chapters)])
            ->sum(fn (?string $chapter) => $this->page($chapter)->minutes);
    }
}
