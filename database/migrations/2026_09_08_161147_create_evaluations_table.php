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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary(); // id (UUID)
            $table->foreignUuid('evaluateur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('evalue_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('reservation_id');
            $table->foreign('reservation_id')->references('id')->on('reservations')->unique()->onDelete('cascade');
            $table->unsignedTinyInteger('note'); // 1 à 5 par exemple, à valider côté FormRequest
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
