<?php

use App\Models\Athlete;
use App\Models\Donator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Donator::class)->cascadeOnDelete()->cascadeOnUpdate()->constrained();
            $table->foreignIdFor(Athlete::class)->cascadeOnDelete()->cascadeOnUpdate()->constrained();
            $table->float("amount_per_round");
            $table->float("amount_max");
            $table->float("amount_min");
            $table->text("comment")->nullable();
            $table->boolean("verified")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
