<?php

use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Athletes
        Schema::table('athletes', function (Blueprint $table) {
            $table->foreignIdFor(SportType::class)->constrained();
            $table->foreignIdFor(Partner::class)->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Athletes
        Schema::table('athletes', function (Blueprint $table) {
            $table->dropForeignIdFor(SportType::class);
            $table->dropForeignIdFor(Partner::class);
        });
    }
};
