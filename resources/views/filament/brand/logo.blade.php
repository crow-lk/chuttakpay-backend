@php
    $brandName = \App\Models\Setting::getValue('brand.company_name') ?? config('app.name');
    $logoPath = \App\Models\Setting::getValue('brand.logo_path');
    $logoUrl = $logoPath
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath)
        : asset('images/logo.jpeg');
@endphp

<img
    alt="{{ $brandName }} logo"
    src="{{ $logoUrl }}"
    loading="lazy"
    style="aspect-ratio: 1 / 1;"
    class="h-full w-full rounded-full object-cover shadow-sm"
/>
