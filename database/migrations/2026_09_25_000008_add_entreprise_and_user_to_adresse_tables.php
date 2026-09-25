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
            $table->foreignUuid('user_id')->nullable()->after('voyageur_id')->constrained('users')->nullOnDelete();
            $table->foreignUuid('entreprise_id')->nullable()->after('user_id')->constrained('entreprises')->cascadeOnDelete();
        });

        Schema::table('adresse_recuperations', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->after('voyageur_id')->constrained('users')->nullOnDelete();
            $table->foreignUuid('entreprise_id')->nullable()->after('user_id')->constrained('entreprises')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adresse_depots', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropForeign(['entreprise_id']);
            $table->dropColumn('entreprise_id');
        });

        Schema::table('adresse_recuperations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropForeign(['entreprise_id']);
            $table->dropColumn('entreprise_id');
        });
    }
};
