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
        Schema::table('voyages', function (Blueprint $table) {
            // Rendre voyageur_id nullable pour les voyages d'entreprise
            $table->uuid('voyageur_id')->nullable()->change();

            $table->uuid('entreprise_id')->nullable()->after('voyageur_id');
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');

            $table->uuid('agent_gp_id')->nullable()->after('entreprise_id');
            $table->foreign('agent_gp_id')->references('id')->on('agent_gps')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropForeign(['agent_gp_id']);
            $table->dropColumn('agent_gp_id');

            $table->dropForeign(['entreprise_id']);
            $table->dropColumn('entreprise_id');

            $table->uuid('voyageur_id')->nullable(false)->change();
        });
    }
};
