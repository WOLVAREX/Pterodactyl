<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('resource_prices', function (Blueprint $table) {
            $table->string('resource_key', 30)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('resource_prices', function (Blueprint $table) {
            $table->dropColumn('resource_key');
        });
    }
};
