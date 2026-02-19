@php
    $logoUrl = asset('images/logo.jpeg');
@endphp

<img
    alt="{{ config('app.name') }} logo"
    src="{{ $logoUrl }}"
    loading="lazy"
    style="aspect-ratio: 1 / 1;"
    class="h-full w-full rounded-full object-cover shadow-sm"
/>
