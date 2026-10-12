<?php

namespace Tests\Feature;

use App\Support\Blog\Guide;
use Tests\TestCase;

class BlogTest extends TestCase
{
    public function test_blog_index_lists_guides(): void
    {
        $this->get('/blog')
            ->assertOk()
            ->assertSee('Deploying to AWS with Terraform')
            ->assertSee(route('blog.guide', 'terraform-aws'), false);
    }

    public function test_blog_is_linked_from_main_navigation(): void
    {
        $this->get('/about')->assertSee(route('blog.index'), false);
    }

    public function test_guide_landing_page_renders_introduction_and_steps(): void
    {
        $guide = Guide::find('terraform-aws');

        $response = $this->get(route('blog.guide', $guide->slug))
            ->assertOk()
            ->assertSee('<h2 id="what-is-terraform">', false)
            ->assertSee('Start the guide');

        foreach ($guide->chapters as $title) {
            $response->assertSee($title);
        }
    }

    public function test_every_chapter_renders_with_navigation(): void
    {
        $guide = Guide::find('terraform-aws');
        $slugs = array_keys($guide->chapters);

        foreach ($slugs as $index => $slug) {
            $response = $this->get(route('blog.chapter', [$guide->slug, $slug]))
                ->assertOk()
                ->assertSee($guide->chapters[$slug])
                ->assertSee('Step '.($index + 1).' of '.count($slugs));

            if (isset($slugs[$index + 1])) {
                $response->assertSee($guide->chapters[$slugs[$index + 1]]);
            }
        }
    }

    public function test_markdown_tables_and_external_links_render(): void
    {
        $this->get(route('blog.chapter', ['terraform-aws', 'aws-resources']))
            ->assertSee('<table>', false)
            ->assertSee('target="_blank"', false);
    }

    public function test_internal_links_between_chapters_resolve(): void
    {
        $guide = Guide::find('terraform-aws');

        foreach ([null, ...array_keys($guide->chapters)] as $chapter) {
            preg_match_all('#href="/blog/terraform-aws/([a-z0-9-]+)#', $guide->page($chapter)->html, $matches);

            foreach ($matches[1] as $linked) {
                $this->assertTrue($guide->hasChapter($linked), "Broken link to [{$linked}] in [".($chapter ?? 'index').']');
            }
        }
    }

    public function test_unknown_guide_and_chapter_return_404(): void
    {
        $this->get('/blog/nope')->assertNotFound();
        $this->get('/blog/terraform-aws/nope')->assertNotFound();
    }
}
