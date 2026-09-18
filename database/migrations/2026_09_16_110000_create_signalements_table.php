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
        Schema::create('signalements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signaleur_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('signale_id')->constrained('users')->onDelete('cascade');
            $table->string('motif'); // Comportement inapproprié, Fraude / Arnaque, Faux profil, Non respect des engagements, Autre
            $table->text('description')->nullable();
            $table->enum('statut', ['en_attente', 'traite', 'rejete'])->default('en_attente');
            $table->string('decision')->nullable(); // bloque, avertissement, sans_suite
            $table->foreignUuid('traite_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
