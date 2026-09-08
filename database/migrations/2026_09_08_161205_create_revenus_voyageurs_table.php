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
        Schema::create('revenus_voyageurs', function (Blueprint $table) {
            $table->uuid('id')->primary(); // id (UUID)
            $table->foreignUuid('voyageur_id')->constrained('voyageurs')->cascadeOnDelete();
            $table->foreignUuid('reservation_id')->unique()->constrained('reservations')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->enum('statut', ['en_attente', 'disponible', 'retire'])->default('en_attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenus_voyageurs');
    }
};
