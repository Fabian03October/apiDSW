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
        Schema::create('contenidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subtema_id')->constrained('subtemas')->onDelete('cascade');
            $table->string('titulo', 255);
            $table->longText('cuerpo');
            $table->enum('tipo_contenido', ['TEXTO', 'VIDEO', 'IMAGEN', 'DOCUMENTO']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contenidos');
    }
};
