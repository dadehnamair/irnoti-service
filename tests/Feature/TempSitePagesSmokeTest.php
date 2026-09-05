<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Page;
use App\Models\RepresentationTier;
use App\Models\SiteFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempSitePagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_page_renders(): void
    {
        Faq::create(['question' => 'سوال؟', 'answer' => 'جواب.', 'is_active' => true, 'sort' => 1]);
        $this->get('/faq')->assertOk()->assertSee('application/ld+json', false);
    }

    public function test_features_page_renders(): void
    {
        SiteFeature::create(['title' => 'ویژگی', 'description' => 'توضیح', 'category' => 'sms', 'is_active' => true, 'is_featured' => true, 'sort' => 1]);
        $this->get('/features')->assertOk()->assertSee('application/ld+json', false);
    }

    public function test_contact_page_renders_and_submits(): void
    {
        $this->get('/contact')->assertOk()->assertSee('application/ld+json', false);

        $this->post('/contact', [
            'name' => 'علی',
            'mobile' => '09121234567',
            'message' => 'سلام، یک پیام آزمایشی.',
        ])->assertRedirect(route('contact'));

        $this->assertDatabaseHas('contact_messages', ['name' => 'علی']);
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

        $this->assertDatabaseHas('representation_applications', ['full_name' => 'رضا']);
    }

    public function test_about_and_cooperation_pages_render(): void
    {
        Page::create(['slug' => 'about', 'title' => 'درباره ما', 'body' => 'متن', 'is_published' => true]);
        Page::create(['slug' => 'cooperation', 'title' => 'همکاری با ما', 'body' => 'متن', 'is_published' => true]);

        $this->get('/about')->assertOk()->assertSee('application/ld+json', false);
        $this->get('/cooperation')->assertOk()->assertSee('application/ld+json', false);
    }

    public function test_unpublished_page_404s(): void
    {
        Page::create(['slug' => 'about', 'title' => 'درباره ما', 'body' => 'متن', 'is_published' => false]);
        $this->get('/about')->assertNotFound();
    }

    public function test_sitemap_still_renders(): void
    {
        $this->get('/sitemap.xml')->assertOk();
    }
}
