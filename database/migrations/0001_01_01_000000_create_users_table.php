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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary(); // id (UUID)
            $table->string('nom'); // nom
            $table->string('prenom'); // prenom
            $table->string('telephone')->unique(); // telephone (unique pour la connexion)
            $table->string('email')->unique(); // email (unique pour la connexion)
            $table->string('avatar')->nullable(); // avatar (chemin de l'image, nullable)
            $table->text('adresse')->nullable(); // adresse (text si longue, nullable)
            
            // statut : enum( actif , inactif ,suspendu ) avec 'actif' par défaut
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif'); 
            
            $table->string('mot_de_passe'); // mot_de_passe
            $table->timestamp('dernier_connexion')->nullable(); // dernier_connexion (date/heure, nullable)
            
            $table->rememberToken(); // Requis par Laravel pour "se souvenir de moi"
            $table->timestamps(); // créé_le et modifié_le (created_at, updated_at)
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
