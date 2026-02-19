<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('type')->default('offline')->after('code');
            $table->string('gateway')->nullable()->after('type');
            $table->unsignedTinyInteger('sort_order')->default(0)->after('gateway');
            $table->text('instructions')->nullable()->after('description');
            $table->json('settings')->nullable()->after('instructions');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cart_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
            $table->string('gateway')->nullable()->after('payment_method_id');
            $table->json('gateway_payload')->nullable()->after('gateway');
            $table->json('gateway_response')->nullable()->after('gateway_payload');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'gateway',
                'sort_order',
                'instructions',
                'settings',
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cart_id');
            $table->dropColumn([
                'gateway',
                'gateway_payload',
                'gateway_response',
            ]);
        });
    }
};
