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
        Schema::create('pickups', function (Blueprint $table) {
            $table->id('pickup_id');
            $table->foreignId('driver_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
            $table->date('date');
            $table->string('status')->default('ASSIGNED');
            $table->string('destination', 1000);
            $table->timestamps();

            $table->index(['driver_id', 'date']);
            $table->index(['agent_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickups');
    }
};
