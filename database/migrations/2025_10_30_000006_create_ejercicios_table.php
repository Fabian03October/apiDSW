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
            
            // CORRECCIÓN 1: Cambiado a string o minúsculas para que coincida con el Seeder ('facil', 'medio')
            $table->string('dificultad', 20)->default('medio'); 
            
            // Tus columnas nuevas
            $table->string('tipo_interaccion', 30)->default('texto_libre'); 
            $table->json('contenido_juego')->nullable();
            
            // JSON metadata si lo tenías antes (opcional, si no lo usas puedes quitarlo)
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
