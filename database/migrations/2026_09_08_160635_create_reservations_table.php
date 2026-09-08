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
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->uuid('voyage_id');
            $table->foreign('voyage_id')->references('id')->on('voyages')->onDelete('cascade');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->decimal('montant_total', 10, 2);
            $table->enum('mode_paiement_souhaite', ['wave', 'espece_depot', 'livraison']);
            $table->enum('statut', ['en_attente', 'acceptee', 'refusee', 'annulee'])->default('en_attente');
            $table->dateTime('date_demande')->nullable();
            $table->dateTime('date_acceptation')->nullable();
            $table->dateTime('date_refus')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
