<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ejercicio extends Model
{
    use HasFactory;

    // 1. Agrega los nuevos campos al fillable para permitir inserciones
    protected $fillable = [
        'subtema_id',
        'titulo',
        'pregunta',
        'solucion',
        'dificultad',
        'tipo_interaccion', // Nuevo
        'contenido_juego',  // Nuevo
    ];

    // 2. IMPORTANTE: Cast para que Laravel convierta el JSON automáticamente a Array
    protected $casts = [
        'contenido_juego' => 'array', 
    ];

    // Relación inversa (Un ejercicio pertenece a un subtema)
    public function subtema()
    {
        return $this->belongsTo(Subtema::class);
    }
}