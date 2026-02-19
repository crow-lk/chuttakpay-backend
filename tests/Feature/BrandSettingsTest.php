<?php

namespace Tests\Feature;

use App\Filament\Pages\BrandSettings;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrandSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_brand_settings(): void
    {
        $user = User::factory()->create();

        Filament::setCurrentPanel('admin');

        $this->actingAs($user);

        Livewire::test(BrandSettings::class)
            ->set('data.company_name', 'Acme Co')
            ->set('data.logo_path', 'branding/logo.png')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Acme Co', Setting::getValue('brand.company_name'));
        $this->assertSame('branding/logo.png', Setting::getValue('brand.logo_path'));
    }
}
