<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroImageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_hero_image(): void
    {
        Setting::setValue('hero.image_path', 'hero-images/hero.jpg');

        $response = $this->getJson('/api/settings/hero-image');

        $response->assertOk()
            ->assertJson([
                'image_path' => 'hero-images/hero.jpg',
                'image_url' => Storage::disk('public')->url('hero-images/hero.jpg'),
            ]);
    }
}
