<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subtema extends Model
{
    protected $table = 'subtemas';
    protected $fillable = ['tema_id', 'titulo', 'descripcion', 'informacion'];

    public function tema(): BelongsTo
    {
        return $this->belongsTo(Tema::class, 'tema_id');
    }

    public function contenidos(): HasMany
    {
        return $this->hasMany(Contenido::class, 'subtema_id');
    }

    public function ejemplos(): HasMany
    {
        return $this->hasMany(Ejemplo::class, 'subtema_id');
    }

    public function ejercicios(): HasMany
    {
        return $this->hasMany(Ejercicio::class, 'subtema_id');
    }

    public function avanceUsuarios(): HasMany
    {
        return $this->hasMany(AvanceUsuario::class, 'subtema_id');
    }
}
