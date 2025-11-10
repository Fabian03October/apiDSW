<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ejercicio extends Model
{
    protected $table = 'ejercicios';
    protected $fillable = ['subtema_id', 'titulo', 'pregunta', 'solucion', 'dificultad', 'metadatos'];
    protected $casts = ['metadatos' => 'array'];

    public function subtema(): BelongsTo
    {
        return $this->belongsTo(Subtema::class, 'subtema_id');
    }
}
