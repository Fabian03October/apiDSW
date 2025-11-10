<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tema extends Model
{
    protected $table = 'temas';
    protected $fillable = ['materia_id', 'titulo', 'descripcion'];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }

    public function subtemas(): HasMany
    {
        return $this->hasMany(Subtema::class, 'tema_id');
    }
}
