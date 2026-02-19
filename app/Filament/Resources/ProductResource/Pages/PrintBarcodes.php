<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class PrintBarcodes extends Page
{
    protected static string $resource = ProductResource::class;

    protected string $view = 'filament.resources.product-resource.pages.print-barcodes';

    public Product $record;

    /**
     * @var EloquentCollection<int, ProductVariant>
     */
    public EloquentCollection $variants;

    public string $productName;

    public function mount(Product $record, ?string $variants = null): void
    {
        $this->record = $record;
        $this->productName = $record->name;

        $ids = collect(explode(',', (string) $variants))
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();

        $this->variants = $record->variants()
            ->with(['size', 'colors'])
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereIn('id', $ids))
            ->get();
    }
}
