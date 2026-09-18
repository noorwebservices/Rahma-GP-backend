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
        Schema::table('voyageurs', function (Blueprint $table) {
            $table->string('verification_token')->nullable()->after('statut');
            $table->timestamp('email_verifie_at')->nullable()->after('verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voyageurs', function (Blueprint $table) {
            $table->dropColumn(['verification_token', 'email_verifie_at']);
        });
    }
};
