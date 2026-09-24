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
        Schema::create('transactions_client', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable()->unique();
            $table->foreignId('price_id')->constrained('prices', 'price_id')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_price', 15, 2);
            $table->decimal('volume_liter', 12, 3);
            $table->string('status')->default('PENDING');
            $table->string('transaction_note', 1000)->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status', 'created_at']);
            $table->index(['agent_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions_client');
    }
};
