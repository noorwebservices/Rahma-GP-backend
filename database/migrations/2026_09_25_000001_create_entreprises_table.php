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
        Schema::create('entreprises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('gerant_user_id');
            $table->foreign('gerant_user_id')->references('id')->on('users')->onDelete('cascade');

            // Informations Générales
            $table->string('nom');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('telephone');
            $table->string('email')->unique();
            $table->text('adresse');
            $table->string('ville');
            $table->string('pays');

            // Informations Légales (Colonnes Explicites)
            $table->string('ninea')->nullable();
            $table->string('registre_commerce')->nullable();
            $table->string('ninea_doc')->nullable();
            $table->string('registre_commerce_doc')->nullable();
            $table->json('autres_documents_legaux')->nullable();

            // Statut de vérification
            $table->enum('statut_verification', ['en_attente', 'verifiee', 'suspendue'])->default('en_attente');

            // Informations de Paiement
            $table->string('moyen_paiement_prefere')->default('wave');
            $table->json('coordonnees_paiement')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprises');
    }
};
