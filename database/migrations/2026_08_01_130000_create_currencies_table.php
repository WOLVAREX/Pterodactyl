<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('symbol', 10);
            $table->decimal('rate_to_kes', 14, 6)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('currencies')->insert([
            'code' => 'KES',
            'symbol' => 'KSh',
            'rate_to_kes' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
