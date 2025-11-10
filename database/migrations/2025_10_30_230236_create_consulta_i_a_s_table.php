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
        Schema::create('consulta_i_a_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ejercicio_id')->nullable()->constrained('ejercicios')->onDelete('cascade');
            $table->text('pregunta');
            $table->text('respuesta_ia')->nullable();
            $table->text('retroalimentacion')->nullable();
            $table->enum('tipo', ['duda', 'respuesta_ejercicio'])->default('duda');
            $table->boolean('es_correcto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consulta_i_a_s');
    }
};
