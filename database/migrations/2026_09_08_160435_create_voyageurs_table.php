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
        Schema::create('voyageurs', function (Blueprint $table) {
            $table->uuid('id')->primary(); // id (UUID)
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('type_piece', ['cni', 'passport'])->default('cni');
            $table->string('numero_piece')->nullable();
            $table->string('cni_recto')->nullable();
            $table->string('cni_verso')->nullable();
            $table->boolean('mode_client')->default(true); // true = mode client, false = mode voyageur
            $table->enum('statut', ['en_attente', 'verifie', 'refuse'])->default('en_attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voyageurs');
    }
};
