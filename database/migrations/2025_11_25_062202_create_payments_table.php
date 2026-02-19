<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // e.g. Cash, Card, Bank Transfer
            $table->string('code')->nullable();   // short code, e.g. CASH, CARD
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount_paid', 15, 2);
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('discount_available')->default(false);
            $table->decimal('discount', 15, 2)->nullable();
            $table->string('payment_status')->default('pending'); // paid, pending, partial, failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
    }
};

