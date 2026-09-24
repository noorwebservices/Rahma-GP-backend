<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajoute les statuts 'ferme' et 'cloture' manquants à l'enum voyages.statut.
     * Sans eux, closePastVoyages() (qui écrit 'ferme') échoue en "Data truncated".
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE voyages MODIFY statut ENUM('brouillon','publie','complet','en_cours','ferme','cloture','termine','annule') NOT NULL DEFAULT 'brouillon'");
    }

    public function down(): void
    {
        // Rétablir les valeurs non supportées avant de réduire l'enum.
        DB::statement("UPDATE voyages SET statut = 'termine' WHERE statut IN ('ferme','cloture')");
        DB::statement("ALTER TABLE voyages MODIFY statut ENUM('brouillon','publie','complet','en_cours','termine','annule') NOT NULL DEFAULT 'brouillon'");
    }
};
