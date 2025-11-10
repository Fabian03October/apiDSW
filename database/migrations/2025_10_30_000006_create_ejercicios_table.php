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
        Schema::create('ejercicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subtema_id')->constrained('subtemas')->onDelete('cascade');
            $table->string('titulo', 255);
            $table->text('pregunta');
            $table->text('solucion');
            $table->enum('dificultad', ['FACIL', 'MEDIO', 'DIFICIL']);
            $table->json('metadatos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ejercicios');
    }
};
