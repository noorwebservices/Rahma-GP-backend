<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cache des résolutions IP -> pays pour éviter d'appeler l'API externe à répétition.
        Schema::create('ip_geolocations', function (Blueprint $table) {
            $table->string('ip_address', 45)->primary();
            $table->string('country')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_geolocations');
    }
};
