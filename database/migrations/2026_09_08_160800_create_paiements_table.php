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
        Schema::create('paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reservation_id')->unique()->constrained('reservations')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->string('reference')->nullable();
            $table->enum('mode_paiement', ['wave', 'espece_depot', 'livraison']);
            $table->enum('statut', ['en_attente', 'reussi', 'echoue'])->default('en_attente');
            $table->dateTime('date_paiement')->nullable();
            $table->foreignUuid('confirme_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
