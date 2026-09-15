<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trabajador extends Model implements AuthenticatableContract
{
    use Authenticatable;

    public $timestamps = false;

    protected $table = 'trabajador';

    protected $primaryKey = 'id_trab';

    protected $fillable = ['nombre', 'apellido', 'telefono', 'email', 'direccion', 'id_rol', 'password'];

    protected $hidden = ['password'];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function pagosEmpleado(): HasMany
    {
        return $this->hasMany(PagoEmpleado::class, 'id_trab');
    }

    public function reportesComoModelo(): HasMany
    {
        return $this->hasMany(ReportePago::class, 'id_modelo');
    }

    public function reportesComoModerador(): HasMany
    {
        return $this->hasMany(ReportePago::class, 'id_moderador');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre.' '.$this->apellido);
    }
}
