<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Closure;
use Illuminate\Support\Str;

class IdentifierService
{
    /**
     * @param  iterable<int, \App\Models\Color>  $colors
     */
    public function makeSku(Product $product, ?Size $size = null, iterable $colors = []): string
    {
        $base = Str::upper($product->sku_prefix ?: str_replace('-', '', $product->slug ?: Str::slug($product->name)));
        $base = $base !== '' ? $base : 'PRD';

        $segments = [$base];

        if ($size !== null) {
            $segments[] = $this->codeFromName($size->name);
        }

        $colorCollection = collect($colors)
            ->filter()
            ->unique(fn ($color) => $color?->id ?? $color?->name);

        foreach ($colorCollection as $color) {
            if ($color?->name) {
                $segments[] = $this->codeFromName($color->name);
            }
        }

        $candidate = implode('-', array_filter($segments));

        return $this->ensureUnique('sku', $candidate);
    }

    public function makeBarcode(ProductVariant $variant): string
    {
        $productId = $variant->product_id ?? $variant->product?->getKey() ?? 0;
        $base = str_pad((string) $productId, 6, '0', STR_PAD_LEFT);

        return $this->ensureUnique('barcode', function () use ($base): string {
            $random = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            return "9{$base}{$random}";
        });
    }

    protected function ensureUnique(string $column, string|Closure $value): string
    {
        $attempt = 1;

        do {
            $candidate = is_string($value)
                ? ($attempt === 1 ? $value : "{$value}-{$attempt}")
                : $value();

            $exists = ProductVariant::query()
                ->where($column, $candidate)
                ->exists();

            $attempt++;
        } while ($exists);

        return $candidate;
    }

    protected function codeFromName(string $name): string
    {
        $code = Str::upper(preg_replace('/[^A-Z0-9]/', '', Str::ascii($name)));

        if ($code === '') {
            $code = Str::upper(Str::random(3));
        }

        return mb_substr($code, 0, 3);
    }
}
