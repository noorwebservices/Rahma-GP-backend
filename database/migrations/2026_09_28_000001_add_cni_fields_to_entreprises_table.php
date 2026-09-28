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
        Schema::table('entreprises', function (Blueprint $table) {
            $table->enum('type_piece', ['cni', 'passport'])->default('cni')->after('registre_commerce_doc');
            $table->string('numero_piece')->nullable()->after('type_piece');
            $table->string('cni_recto')->nullable()->after('numero_piece');
            $table->string('cni_verso')->nullable()->after('cni_recto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['type_piece', 'numero_piece', 'cni_recto', 'cni_verso']);
        });
    }
};
