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
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reservation_id');
            $table->foreign('reservation_id')->references('id')->on('reservations')->unique()->onDelete('cascade');
            $table->foreignUuid('expediteur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('destinataire_id')->constrained('users')->cascadeOnDelete();
            $table->text('contenu');
            $table->string('piece_jointe')->nullable();
            $table->boolean('est_lu')->default(false);
            $table->dateTime('date_heure_envoi');
            $table->dateTime('date_heure_lecture')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
