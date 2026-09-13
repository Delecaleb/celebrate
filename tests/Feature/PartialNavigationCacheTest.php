<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client-side router fetches pages as JSON from the same URL that serves
 * the full HTML page. If a browser caches that JSON under the page's address,
 * pressing Back shows it raw — the "entire source code" bug.
 *
 * So the JSON is never stored, and both representations say they vary on the
 * header that tells them apart.
 */
class PartialNavigationCacheTest extends TestCase
{
    use RefreshDatabase;

    private function assertNeverStored($response): void
    {
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('X-Partial', (string) $response->headers->get('Vary'));
    }

    public function test_a_marketing_partial_is_never_stored_by_the_browser(): void
    {
        $response = $this->get(route('features') . '?_partial=1', ['X-Partial' => '1', 'Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['title', 'html']);

        $this->assertNeverStored($response);
    }

    public function test_the_full_marketing_page_varies_on_the_partial_header(): void
    {
        $response = $this->get(route('features'))->assertOk();

        $this->assertStringContainsString('<html', $response->getContent());
        $this->assertStringContainsString('X-Partial', (string) $response->headers->get('Vary'));
    }

    public function test_a_dashboard_partial_is_never_stored_by_the_browser(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('dashboard') . '?_partial=1', ['X-Partial' => '1', 'Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['title', 'html']);

        $this->assertNeverStored($response);
    }

    public function test_the_full_dashboard_page_varies_on_the_partial_header(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk();

        $this->assertStringContainsString('X-Partial', (string) $response->headers->get('Vary'));
    }
}
