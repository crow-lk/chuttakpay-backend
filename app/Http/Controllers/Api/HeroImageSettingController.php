<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class HeroImageSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $imagePath = Setting::getValue('hero.image_path');

        return response()->json([
            'image_path' => $imagePath,
            'image_url' => $this->resolveImageUrl($imagePath),
        ]);
    }

    private function resolveImageUrl(?string $imagePath): ?string
    {
        if (blank($imagePath)) {
            return null;
        }

        return Storage::disk('public')->url($imagePath);
    }
}
