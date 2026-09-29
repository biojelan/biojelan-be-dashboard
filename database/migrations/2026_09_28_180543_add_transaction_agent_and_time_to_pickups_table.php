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
        Schema::table('pickups', function (Blueprint $table) {
            $table->foreignId('transaction_agent_id')
                ->unique()
                ->constrained('transactions_agent')
                ->restrictOnDelete()
                ->after('pickup_id');
            $table->time('time')->after('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropForeign(['transaction_agent_id']);
            $table->dropUnique(['transaction_agent_id']);
            $table->dropColumn(['transaction_agent_id', 'time']);
        });
    }
};
