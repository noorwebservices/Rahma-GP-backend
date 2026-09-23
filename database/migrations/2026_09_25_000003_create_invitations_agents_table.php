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
        Schema::create('invitations_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('entreprise_id');
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');

            $table->enum('canal', ['email', 'whatsapp', 'sms'])->default('email');
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();

            $table->string('token')->unique();
            $table->text('lien_invitation')->nullable();

            $table->enum('statut', ['en_attente', 'acceptee', 'expiree', 'annulee'])->default('en_attente');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations_agents');
    }
};
