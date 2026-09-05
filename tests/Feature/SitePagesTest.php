<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Page;
use App\Models\RepresentationTier;
use App\Models\SiteFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public "site content" pages — FAQ, Features, Contact, generic Pages
 * (About/Cooperation) — and the adjacent Representation lead-capture flow.
 * See docs/site-content.md and docs/sales-representation.md.
 */
class SitePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_page_lists_active_questions(): void
    {
        Faq::create(['question' => 'سوال فعال؟', 'answer' => 'جواب.', 'is_active' => true, 'sort' => 1]);
        Faq::create(['question' => 'سوال غیرفعال؟', 'answer' => 'جواب.', 'is_active' => false, 'sort' => 2]);

        $response = $this->get('/faq');

        $response->assertOk()->assertSee('application/ld+json', false);
        $response->assertSee('سوال فعال؟');
        $response->assertDontSee('سوال غیرفعال؟');
    }

    public function test_features_page_renders(): void
    {
        SiteFeature::create([
            'title' => 'ویژگی آزمایشی',
            'description' => 'توضیح',
            'category' => 'sms',
            'is_active' => true,
            'is_featured' => true,
            'sort' => 1,
        ]);

        $response = $this->get('/features');

        $response->assertOk()->assertSee('application/ld+json', false);
        $response->assertSee('ویژگی آزمایشی');
    }

    public function test_contact_page_renders_and_submits(): void
    {
        $this->get('/contact')->assertOk()->assertSee('application/ld+json', false);

        $this->post('/contact', [
            'name' => 'علی',
            'mobile' => '09121234567',
            'message' => 'سلام، یک پیام آزمایشی.',
        ])->assertRedirect(route('contact'));

        $this->assertDatabaseHas('contact_messages', ['name' => 'علی', 'status' => 'new']);
    }

    public function test_contact_form_requires_name_mobile_and_message(): void
    {
        $this->post('/contact', [])->assertSessionHasErrors(['name', 'mobile', 'message']);
    }

    public function test_representation_page_renders_and_applies(): void
    {
        $tier = RepresentationTier::create([
            'name' => 'نمایندگی آغازین',
            'slug' => 'starter',
            'commission_percent' => 10,
            'is_active' => true,
            'sort' => 1,
        ]);

        $this->get('/representation')->assertOk()->assertSee('application/ld+json', false);

        $this->post('/representation', [
            'representation_tier_id' => $tier->id,
            'full_name' => 'رضا',
            'mobile' => '09121234567',
        ])->assertRedirect(route('representation'));

        $this->assertDatabaseHas('representation_applications', [
            'full_name' => 'رضا',
            'representation_tier_id' => $tier->id,
            'status' => 'pending',
        ]);
    }

    public function test_about_and_cooperation_pages_render(): void
    {
        Page::create(['slug' => 'about', 'title' => 'درباره ما', 'body' => 'متن درباره ما', 'is_published' => true]);
        Page::create(['slug' => 'cooperation', 'title' => 'همکاری با ما', 'body' => 'متن همکاری', 'is_published' => true]);

        $this->get('/about')->assertOk()->assertSee('application/ld+json', false)->assertSee('متن درباره ما');
        $this->get('/cooperation')->assertOk()->assertSee('application/ld+json', false)->assertSee('متن همکاری');
    }

    public function test_unpublished_page_is_not_found(): void
    {
        Page::create(['slug' => 'about', 'title' => 'درباره ما', 'body' => 'متن', 'is_published' => false]);

        $this->get('/about')->assertNotFound();
    }

    public function test_sitemap_includes_the_new_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('features'));
        $response->assertSee(route('faq'));
        $response->assertSee(route('contact'));
        $response->assertSee(route('representation'));
    }
}
