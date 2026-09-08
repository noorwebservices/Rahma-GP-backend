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
        Schema::create('voyages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('voyageur_id');
            $table->foreign('voyageur_id')->references('id')->on('voyageurs')->onDelete('cascade');
            $table->uuid('adresse_depot_id');
            $table->foreign('adresse_depot_id')->references('id')->on('adresse_depots')->onDelete('cascade');
            $table->uuid('adresse_recuperation_id');
            $table->foreign('adresse_recuperation_id')->references('id')->on('adresse_recuperations')->onDelete('cascade');
            $table->string('pays_depart');
            $table->string('ville_depart');
            $table->string('pays_destination');
            $table->string('ville_destination');
            $table->dateTime('date_depart');
            $table->dateTime('date_arrivee');
            $table->float('capacite_totale');
            $table->float('capacite_dispo');
            $table->decimal('prix_kg', 10, 2)->nullable();
            $table->decimal('prix_objet', 10, 2)->nullable();
            $table->string('devise')->default('XOF');
            $table->text('description')->nullable();
            $table->json('objets_autorises')->nullable();
            $table->json('objets_interdits')->nullable();            
            $table->enum('statut', ['brouillon', 'publie', 'complet', 'en_cours', 'termine', 'annule'])
                  ->default('brouillon');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voyages');
    }
};
