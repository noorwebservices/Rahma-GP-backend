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
        Schema::create('demandes_partenariat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom_complet');
            $table->string('email');
            $table->string('telephone');
            $table->string('entreprise')->nullable();
            $table->string('type_partenariat')->default('autre'); // transporteur, agence, entreprise, autre
            $table->text('message');
            $table->enum('statut', ['en_attente', 'contacte', 'traite', 'archive'])->default('en_attente');
            $table->text('notes_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_partenariat');
    }
};
