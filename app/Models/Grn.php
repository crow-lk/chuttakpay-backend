<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Grn extends Model
{
    /** @use HasFactory<\Database\Factories\GrnFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grn_number',
        'supplier_id',
        'purchase_order_id',
        'received_date',
        'status',
        'remarks',
        'location_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $grn): void {
            if (blank($grn->grn_number)) {
                $grn->grn_number = static::generateGrnNumber();
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function grnItems(): HasMany
    {
        return $this->hasMany(GrnItem::class);
    }

    /**
     * Generate unique GRN number in format GRN-0001, GRN-0002, etc.
     */
    public static function generateGrnNumber(): string
    {
        return DB::transaction(function (): string {
            $lastGrn = static::withTrashed()
                ->where('grn_number', 'like', 'GRN-%')
                ->orderByRaw('CAST(SUBSTRING(grn_number, 5) AS UNSIGNED) DESC')
                ->first();

            if ($lastGrn) {
                $lastNumber = (int) substr($lastGrn->grn_number, 4);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            return 'GRN-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Update stock levels when GRN is verified
     */
    public function updateStockLevels(): void
    {
        if ($this->status !== 'verified') {
            return;
        }

        foreach ($this->grnItems as $item) {
            $stockLevel = StockLevel::firstOrCreate(
                [
                    'location_id' => $this->location_id,
                    'variant_id' => $item->variant_id ?? $item->product->variants->first()?->id,
                ],
                [
                    'on_hand' => 0,
                    'reserved' => 0,
                ]
            );

            $stockLevel->increment('on_hand', $item->received_qty);

            // Create stock movement record
            StockMovement::create([
                'variant_id' => $stockLevel->variant_id,
                'location_id' => $this->location_id,
                'type' => 'in',
                'quantity' => $item->received_qty,
                'reference_type' => static::class,
                'reference_id' => $this->id,
                'notes' => "GRN {$this->grn_number} - {$item->product->name}",
            ]);
        }
    }
}
