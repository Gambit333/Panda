<?php

namespace App\Models;

use App\Support\Permisos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    public $timestamps = false; // La tabla roles no suele necesitar timestamps

    protected $table = 'roles';

    protected $primaryKey = 'id_rol';

    protected $fillable = ['rol', 'permisos'];

    protected function casts(): array
    {
        return [
            'permisos' => 'array',
        ];
    }

    /** Módulos a los que entra este rol, según lo guardado o el acceso por defecto. */
    public function modulos(): array
    {
        return Permisos::modulosDe($this->rol, $this->permisos);
    }

    /** Los permisos de admin/programador no se pueden editar. */
    public function permisosBloqueados(): bool
    {
        return Permisos::esBloqueado($this->rol);
    }

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class, 'id_rol');
    }
}
