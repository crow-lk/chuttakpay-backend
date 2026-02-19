<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Drop existing unique index (important)
            $table->dropUnique(['email']);

            // Make email nullable
            $table->string('email')->nullable()->change();

            // Add unique index again
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropUnique(['email']);

            $table->string('email')->nullable(false)->change();

            $table->unique('email');
        });
    }
};

