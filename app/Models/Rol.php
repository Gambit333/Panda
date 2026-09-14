<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    public $timestamps = false;

    protected $table = 'roles';

    protected $primaryKey = 'id_rol';

    protected $fillable = ['rol'];

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class, 'id_rol');
    }
}
