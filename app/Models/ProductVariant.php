<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STOCK_REASONS = [
        'opening',
        'purchase',
        'sale',
        'return',
        'transfer',
        'correction',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'size_id',
        'cost_price',
        'mrp',
        'selling_price',
        'reorder_point',
        'reorder_qty',
        'weight_grams',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'mrp' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'reorder_point' => 'integer',
            'reorder_qty' => 'integer',
            'weight_grams' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $variant): void {
            /** @var \App\Services\IdentifierService $identifier */
            $identifier = app(\App\Services\IdentifierService::class);

            if (blank($variant->sku) && $variant->product_id !== null) {
                $product = $variant->relationLoaded('product')
                    ? $variant->product
                    : Product::query()->find($variant->product_id);

                $size = $variant->relationLoaded('size') ? $variant->size : ($variant->size_id ? Size::query()->find($variant->size_id) : null);
                $colors = $variant->relationLoaded('colors') ? $variant->colors : collect();

                if ($product !== null) {
                    $variant->sku = $identifier->makeSku($product, $size, $colors);
                }
            }

            if (blank($variant->barcode)) {
                $variant->barcode = $identifier->makeBarcode($variant);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * @return BelongsToMany<Color>
     */
    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class)
            ->withTimestamps()
            ->orderBy('colors.sort_order')
            ->orderBy('colors.name');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id');
    }

    /**
     * Adjust the stock level for a specific location and log the movement.
     *
     * @param  array<string, mixed>  $meta
     */
    public function adjustStock(int $locationId, int $quantity, string $reason, array $meta = []): StockMovement
    {
        if (! in_array($reason, self::STOCK_REASONS, true)) {
            throw new InvalidArgumentException("Invalid stock adjustment reason [{$reason}].");
        }

        if (! $this->exists) {
            throw new InvalidArgumentException('Variant must be persisted before adjusting stock.');
        }

        return DB::transaction(function () use ($locationId, $quantity, $reason, $meta): StockMovement {
            $level = StockLevel::query()
                ->where('variant_id', $this->getKey())
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if ($level === null) {
                $level = new StockLevel([
                    'variant_id' => $this->getKey(),
                    'location_id' => $locationId,
                    'on_hand' => 0,
                    'reserved' => 0,
                ]);
            }

            $level->on_hand += $quantity;

            if (array_key_exists('reserved_delta', $meta)) {
                $level->reserved += (int) $meta['reserved_delta'];
            }

            $level->save();

            return $this->movements()->create([
                'location_id' => $locationId,
                'quantity' => $quantity,
                'reason' => $reason,
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id' => $meta['reference_id'] ?? null,
                'notes' => $meta['notes'] ?? null,
                'created_by' => $meta['created_by'] ?? null,
            ]);
        });
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = [
                $this->product?->name,
            ];

            $chips = [];

            if ($this->size?->name) {
                $chips[] = $this->size->name;
            }

            $colorNames = $this->colors
                ->pluck('name')
                ->filter()
                ->unique()
                ->values();

            if ($colorNames->isNotEmpty()) {
                $chips[] = $colorNames->implode(' / ');
            }

            if ($chips !== []) {
                $parts[] = '('.implode(' / ', $chips).')';
            }

            return trim(implode(' ', array_filter($parts)));
        });
    }

    protected function colorNames(): Attribute
    {
        return Attribute::get(function (): ?string {
            $names = $this->colors
                ->pluck('name')
                ->filter()
                ->unique()
                ->values();

            return $names->isNotEmpty() ? $names->implode(' / ') : null;
        });
    }
}
