<?php

namespace App\Models;

use App\Support\Permisos;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trabajador extends Model implements AuthenticatableContract
{
    use Authenticatable;

    /** Roles que solo ven su parte: el resto entra a todo (o a lo que tenga guardado). */
    public const ROLES_RESTRINGIDOS = Permisos::ROLES_RESTRINGIDOS;

    /** Roles con acceso a todo garantizado (admin y programador no se pueden restringir). */
    public const SUPER_ROLES = ['admin', 'ceo', 'support', 'programador'];

    protected $table = 'trabajador';

    protected $primaryKey = 'id_trab';

    // Desactiva el manejo automático de created_at y updated_at
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'email',
        'password',
        'direccion',
        'id_rol',
        'intentos_fallidos',
        'bloqueado',
        'bloqueado_hasta',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function modelosAsignadas()
    {
        return $this->belongsToMany(
            Trabajador::class,
            'modelo_moderador',
            'id_moderador',
            'id_modelo'
        );
    }

    public function moderadoresAsignados()
    {
        return $this->belongsToMany(
            Trabajador::class,
            'modelo_moderador',
            'id_modelo',
            'id_moderador'
        );
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'bloqueado' => 'boolean',
            'bloqueado_hasta' => 'datetime',
            'intentos_fallidos' => 'integer',
        ];
    }

    /** Intentos fallidos antes de bloquear la cuenta. */
    public const MAX_INTENTOS = 6;

    /** Minutos que dura el bloqueo automático (un programador puede limpiarlo antes). */
    public const MINUTOS_BLOQUEO = 15;

    /** Solo el rol programador puede cambiar contraseñas y desbloquear usuarios. */
    public const ROLES_GESTORES = ['programador'];

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

    /**
     * Acceso total: se puede entrar a todos los módulos y ver todos los reportes.
     * Lo tienen los roles sin permisos guardados salvo modelo/moderador, y los que
     * tengan guardada la lista completa de módulos (admin y programador siempre).
     */
    public function esSuperRol(): bool
    {
        return $this->rol !== null && count($this->modulosPermitidos()) === count(Permisos::MODULOS);
    }

    /** @return array<int, string> */
    public function modulosPermitidos(): array
    {
        return Permisos::modulosDe($this->rol?->rol, $this->rol?->permisos);
    }

    public function puede(string $modulo): bool
    {
        return in_array($modulo, $this->modulosPermitidos(), true);
    }

    /** Programadores: únicos autorizados a cambiar contraseñas y desbloquear cuentas. */
    public function esProgramador(): bool
    {
        return in_array(strtolower((string) ($this->rol?->rol ?? '')), self::ROLES_GESTORES, true);
    }

    public function estaBloqueado(): bool
    {
        if (! $this->bloqueado) {
            return false;
        }

        // El bloqueo expira solo: pasado el plazo la cuenta vuelve a estar disponible.
        return $this->bloqueado_hasta === null || $this->bloqueado_hasta->isFuture();
    }

    public function minutosRestantesBloqueo(): int
    {
        if (! $this->estaBloqueado() || $this->bloqueado_hasta === null) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInMinutes($this->bloqueado_hasta, false)));
    }

    /** Registra un fallo; al llegar al máximo bloquea la cuenta. */
    public function registrarIntentoFallido(): void
    {
        $this->intentos_fallidos = (int) $this->intentos_fallidos + 1;

        if ($this->intentos_fallidos >= self::MAX_INTENTOS) {
            $this->bloqueado = true;
            $this->bloqueado_hasta = now()->addMinutes(self::MINUTOS_BLOQUEO);
        }

        $this->save();
    }

    /** Ingreso correcto: se limpia el contador y cualquier bloqueo. */
    public function limpiarIntentos(): void
    {
        $this->intentos_fallidos = 0;
        $this->bloqueado = false;
        $this->bloqueado_hasta = null;

        $this->save();
    }

    /** Desbloqueo manual hecho por un programador. */
    public function desbloquear(): void
    {
        $this->limpiarIntentos();
    }

    public function tienePassword(): bool
    {
        return filled($this->password);
    }
}
