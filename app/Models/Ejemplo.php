<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ejemplo extends Model
{
    protected $table = 'ejemplos';
    protected $fillable = ['subtema_id', 'titulo', 'cuerpo'];

    public function subtema(): BelongsTo
    {
        return $this->belongsTo(Subtema::class, 'subtema_id');
    }
}
