<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvanceUsuario extends Model
{
    protected $table = 'avance_usuario';
    protected $fillable = ['usuario_id', 'subtema_id', 'completado', 'fecha_completado', 'puntacion', 'notas'];
    protected $casts = ['completado' => 'boolean'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function subtema(): BelongsTo
    {
        return $this->belongsTo(Subtema::class, 'subtema_id');
    }
}
