<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Collection;

class ClearExpiredCollections extends Command
{
    protected $signature = 'collections:clear-expired';
    protected $description = 'Remove collection_id from products when collection is expired';

    public function handle(): int
    {
        $expiredCollectionIds = Collection::whereDate('end_date', '<', now())
            ->pluck('id');

        if ($expiredCollectionIds->isEmpty()) {
            $this->info('No expired collections found.');
            return self::SUCCESS;
        }

        Product::whereIn('collection_id', $expiredCollectionIds)
            ->update(['collection_id' => null]);

        $this->info('Expired collections removed from products.');

        return self::SUCCESS;
    }
}

