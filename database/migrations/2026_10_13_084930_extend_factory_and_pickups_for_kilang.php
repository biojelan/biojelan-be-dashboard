<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory', function (Blueprint $table): void {
            $table->decimal('minimum_pickup_liter', 12, 3)->default(50);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('superadmin_key')->nullable()
                ->virtualAs('CASE WHEN role_id = 1 THEN 1 ELSE NULL END')->unique();
        });
        Schema::table('pickups', function (Blueprint $table): void {
            $table->foreignId('factory_id')->nullable()->constrained('factory', 'factory_id')->restrictOnDelete();
            $table->uuid('route_key')->nullable()->index();
            $table->unsignedInteger('stop_order')->default(1);
            $table->timestamp('completed_at')->nullable();
        });
        $factoryId = DB::table('factory')->orderBy('factory_id')->value('factory_id');
        if ($factoryId !== null) {
            DB::table('pickups')->update(['factory_id' => $factoryId]);
        }
        DB::table('pickups')->where('status', 'COMPLETED')->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('factory_id');
            $table->dropColumn(['route_key', 'stop_order', 'completed_at']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['superadmin_key']);
            $table->dropColumn('superadmin_key');
        });
        Schema::table('factory', function (Blueprint $table): void {
            $table->dropColumn('minimum_pickup_liter');
        });
    }
};
