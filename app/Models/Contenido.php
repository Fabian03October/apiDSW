<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contenido extends Model
{
    protected $table = 'contenidos';
    protected $fillable = ['subtema_id', 'titulo', 'cuerpo', 'tipo_contenido'];

    public function subtema(): BelongsTo
    {
        return $this->belongsTo(Subtema::class, 'subtema_id');
    }
}
