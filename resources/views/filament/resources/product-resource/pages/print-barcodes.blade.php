@php
    $fontUrl = 'https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $productName }} – Barcodes</title>
    <link href="{{ $fontUrl }}" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            margin: 0;
            padding: 2rem;
            background: #f9fafb;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }

        .card {
            background: #ffffff;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 10px 25px -20px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .barcode-text {
            font-family: 'Libre Barcode 128', cursive;
            font-size: 2.5rem;
            line-height: 1;
            letter-spacing: 0.05em;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            font-size: 0.85rem;
        }

        .chip {
            background: #eef2ff;
            color: #4338ca;
            border-radius: 999px;
            padding: 0.2rem 0.6rem;
        }

        .price {
            font-weight: 600;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 1rem;
            }

            .card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; align-items: center;">
        <button onclick="window.print()" style="padding: 0.6rem 1rem; border-radius: 999px; border: none; background:#2563eb; color: white; cursor: pointer;">Print</button>
        <span style="font-size: 0.9rem; color:#4b5563;">Tip: use thicker paper for durable labels.</span>
    </div>

    <h1>{{ $productName }} barcodes</h1>

    <div class="grid">
        @forelse ($variants as $variant)
            @php
                $displayName = $variant->display_name ?? $variant->product->name;
                $chips = array_filter([
                    $variant->size?->name,
                    $variant->color_names,
                ]);
            @endphp
            <div class="card">
                <strong>{{ $displayName }}</strong>
                <div class="meta">
                    <span class="chip">SKU: {{ $variant->sku }}</span>
                    @foreach ($chips as $chip)
                        <span class="chip">{{ $chip }}</span>
                    @endforeach
                    @if ($variant->selling_price)
                        <span class="chip price">Rs. {{ number_format((float) $variant->selling_price, 2) }}</span>
                    @endif
                </div>
                <div class="barcode-text">{{ $variant->barcode }}</div>
                <div style="font-size: 0.85rem; color:#6b7280;">{{ $variant->barcode }}</div>
            </div>
        @empty
            <p>No variants selected.</p>
        @endforelse
    </div>
</body>

</html>
