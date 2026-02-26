<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('color_product_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('color_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['color_id', 'product_variant_id']);
            $table->index('color_id');
            $table->index('product_variant_id');
        });

        DB::table('product_variants')
            ->whereNotNull('color_id')
            ->orderBy('id')
            ->chunkById(200, function ($variants): void {
                $now = now();
                $rows = $variants->map(function ($variant) use ($now): array {
                    return [
                        'color_id' => $variant->color_id,
                        'product_variant_id' => $variant->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                if ($rows !== []) {
                    DB::table('color_product_variant')->insert($rows);
                }
            });

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('color_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->constrained()->nullOnDelete();
            $table->index('color_id');
        });

        DB::table('color_product_variant')
            ->select('product_variant_id', DB::raw('MIN(color_id) as color_id'))
            ->groupBy('product_variant_id')
            ->orderBy('product_variant_id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('product_variants')
                        ->where('id', $row->product_variant_id)
                        ->update(['color_id' => $row->color_id]);
                }
            });

        Schema::dropIfExists('color_product_variant');
    }
};
