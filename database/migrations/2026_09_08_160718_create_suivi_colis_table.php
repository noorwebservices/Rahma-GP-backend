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
        Schema::create('suivi_colis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('colis_id');
            $table->foreign('colis_id')->references('id')->on('colis')->onDelete('cascade');
            
            $table->enum('statut', [
                'demande_envoyee', 'reservation_acceptee', 'colis_depose',
                'colis_pris_en_charge', 'en_transit', 'arrive', 'livre',
            ]);
            $table->dateTime('date_changement');
            $table->text('commentaire')->nullable();
            $table->foreignUuid('mis_a_jour_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suivi_colis');
    }
};
