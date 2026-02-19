<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku_prefix')->nullable();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('season')->nullable();
            $table->text('description')->nullable();
            $table->text('care_instructions')->nullable();
            $table->string('material_composition')->nullable();
            $table->string('hs_code')->nullable();
            $table->foreignId('default_tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'discontinued'])->default('draft');
            $table->softDeletes();
            $table->timestamps();

            $table->index('brand_id');
            $table->index('category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
