<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    protected $table = 'materias';
    protected $fillable = ['titulo', 'descripcion'];

    public function temas(): HasMany
    {
        return $this->hasMany(Tema::class, 'materia_id');
    }
}
