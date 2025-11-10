<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultaIA extends Model
{
    protected $table = 'consulta_i_a_s';

    protected $fillable = [
        'usuario_id',
        'ejercicio_id',
        'pregunta',
        'respuesta_ia',
        'retroalimentacion',
        'tipo',
        'es_correcto',
    ];

    protected $casts = [
        'es_correcto' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class, 'ejercicio_id');
    }
}

