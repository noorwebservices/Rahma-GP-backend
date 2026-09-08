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
        Schema::create('colis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reservation_id')->unique()->constrained('reservations')->cascadeOnDelete();
            $table->string('numero_suivi')->unique();
            $table->string('type');
            $table->string('photo')->nullable();
            $table->text('description')->nullable();
            $table->decimal('valeur_estimee', 10, 2)->nullable();
            $table->float('poids');
            $table->boolean('est_fragile')->default(false);
            $table->string('destinataire_nom');
            $table->string('destinataire_prenom');
            $table->string('destinataire_numero');
            $table->text('destinataire_adresse');
            $table->enum('statut', [
                'demande_envoyee', 'reservation_acceptee', 'colis_depose',
                'colis_pris_en_charge', 'en_transit', 'arrive', 'livre',
            ])->default('demande_envoyee');
            $table->dateTime('date_depot')->nullable();
            $table->dateTime('date_livraison')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colis');
    }
};
