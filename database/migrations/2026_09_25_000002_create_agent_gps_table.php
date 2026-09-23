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
        Schema::create('agent_gps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('entreprise_id');
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');

            $table->string('matricule')->nullable();

            // Statuts de l'agent
            $table->enum('statut', ['en_attente', 'actif', 'indisponible', 'en_voyage', 'desactive'])->default('en_attente');

            // Dates clés de suivi
            $table->timestamp('date_adhesion')->nullable();
            $table->timestamp('date_activation')->nullable();
            $table->timestamp('date_desactivation')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_gps');
    }
};
