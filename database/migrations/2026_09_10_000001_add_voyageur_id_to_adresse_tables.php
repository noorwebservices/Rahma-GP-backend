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
        Schema::table('adresse_depots', function (Blueprint $table) {
            $table->foreignUuid('voyageur_id')->nullable()->after('id')->constrained('voyageurs')->nullOnDelete();
        });

        Schema::table('adresse_recuperations', function (Blueprint $table) {
            $table->foreignUuid('voyageur_id')->nullable()->after('id')->constrained('voyageurs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adresse_depots', function (Blueprint $table) {
            $table->dropForeign(['voyageur_id']);
            $table->dropColumn('voyageur_id');
        });

        Schema::table('adresse_recuperations', function (Blueprint $table) {
            $table->dropForeign(['voyageur_id']);
            $table->dropColumn('voyageur_id');
        });
    }
};
