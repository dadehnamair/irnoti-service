<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO coverage for the blog subsystem (docs/blog.md): per-tag meta, sitemap
 * inclusion of tags + dynamic blog.index lastmod, and RSS <category>/<dc:creator>.
 */
class BlogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_page_uses_tag_seo_fields_with_fallback(): void
    {
        $tag = BlogTag::create(['name' => 'بازاریابی']);

        $response = $this->get('/blog/tag/'.$tag->slug);

        $response->assertOk();
        $response->assertSee('پست‌های برچسب‌خورده با بازاریابی', false);

        $tag->update([
            'meta_title' => 'عنوان اختصاصی برچسب',
            'meta_description' => 'توضیح اختصاصی برچسب',
        ]);

        $response = $this->get('/blog/tag/'.$tag->slug);

        $response->assertOk();
        $response->assertSee('عنوان اختصاصی برچسب', false);
        $response->assertSee('توضیح اختصاصی برچسب', false);
    }

    public function test_sitemap_includes_blog_tags_and_dynamic_blog_index_lastmod(): void
    {
        $tag = BlogTag::create(['name' => 'فروش']);

        $author = User::factory()->create();
        $category = BlogCategory::create(['name' => 'راهنما']);

        $post = BlogPost::create([
            'blog_category_id' => $category->id,
            'author_id' => $author->id,
            'title' => 'مقاله تست',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        $post->touch();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('blog.tag', $tag->slug), false);
        $response->assertSee('<loc>'.route('blog.index').'</loc>', false);
        $response->assertSee('<lastmod>'.$post->fresh()->updated_at->toDateString().'</lastmod>', false);
    }

    public function test_rss_feed_includes_category_and_creator(): void
    {
        $author = User::factory()->create(['name' => 'نویسنده تست']);
        $category = BlogCategory::create(['name' => 'اخبار']);

        BlogPost::create([
            'blog_category_id' => $category->id,
            'author_id' => $author->id,
            'title' => 'خبر تست',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get('/blog/feed');

        $response->assertOk();
        $response->assertSee('xmlns:dc="http://purl.org/dc/elements/1.1/"', false);
        $response->assertSee('<category>اخبار</category>', false);
        $response->assertSee('<dc:creator>نویسنده تست</dc:creator>', false);
    }
}
