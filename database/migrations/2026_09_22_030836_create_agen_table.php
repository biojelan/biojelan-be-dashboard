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
        Schema::create('agen', function (Blueprint $table): void {
            $table->foreignId('agen_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('address');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('bank_name');
            $table->string('account_number');
            $table->time('open_at');
            $table->time('close_at');
            $table->json('open_days');
            $table->boolean('is_open')->default(false);
            $table->float('stock_liter')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agen');
    }
};
