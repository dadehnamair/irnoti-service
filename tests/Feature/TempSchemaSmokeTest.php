<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempSchemaSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('application/ld+json', false);
    }

    public function test_legal_pages_render(): void
    {
        $this->get('/terms')->assertOk()->assertSee('application/ld+json', false);
        $this->get('/privacy')->assertOk()->assertSee('application/ld+json', false);
    }
}
