<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Console\Command;

class StockOpenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:open {variant_id} {location_id} {qty}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the opening stock quantity for a variant at a location.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $variantId = (int) $this->argument('variant_id');
        $locationId = (int) $this->argument('location_id');
        $quantity = (int) $this->argument('qty');

        if ($quantity < 0) {
            $this->components->error('Quantity must be zero or positive for opening stock.');

            return static::FAILURE;
        }

        $variant = ProductVariant::query()->find($variantId);

        if ($variant === null) {
            $this->components->error("Variant with id [{$variantId}] not found.");

            return static::FAILURE;
        }

        $location = Location::query()->find($locationId);

        if ($location === null) {
            $this->components->error("Location with id [{$locationId}] not found.");

            return static::FAILURE;
        }

        $existingOpening = StockMovement::query()
            ->where('variant_id', $variant->getKey())
            ->where('location_id', $location->getKey())
            ->where('reason', 'opening')
            ->exists();

        if ($existingOpening && $quantity > 0) {
            $this->components->error('Opening stock already recorded for this variant and location.');

            return static::FAILURE;
        }

        $variant->adjustStock($location->getKey(), $quantity, 'opening', [
            'notes' => 'Opening stock via command.',
        ]);

        $this->components->info(sprintf(
            'Opening stock applied: variant %d at location %d = %d units.',
            $variant->getKey(),
            $location->getKey(),
            $quantity
        ));

        return static::SUCCESS;
    }
}
