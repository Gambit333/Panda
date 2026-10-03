<?php

namespace App\Support;

/**
 * Módulos de la app y permisos de cada rol.
 *
 * Reglas:
 * - `admin` y `programador` siempre tienen acceso a todo y sus permisos NO se pueden editar.
 * - Un rol con `permisos` guardados (array, aunque esté vacía) usa esa lista tal cual.
 * - Un rol sin `permisos` guardados (null) entra a todo, salvo `modelo` (nada) y
 *   `moderador` (solo reportes), para no dejar a nadie fuera por un rol mal escrito.
 */
class Permisos
{
    /** @var array<string, string> clave del módulo => etiqueta para el formulario */
    public const MODULOS = [
        'reportes' => 'Reportes de pago',
        'cierres' => 'Cierres semanales',
        'pagos' => 'Pagos a empleados',
        'adelantos' => 'Adelantos y préstamos',
        'trabajadores' => 'Trabajadores',
        'metodos' => 'Métodos de pago',
        'roles' => 'Roles',
    ];

    /** Roles con acceso a todo y permisos bloqueados en el formulario. */
    public const ROLES_BLOQUEADOS = ['admin', 'programador'];

    /** Roles con acceso restringido aunque no tengan permisos guardados. */
    public const ROLES_RESTRINGIDOS = ['modelo', 'moderador'];

    /** Permisos por defecto de los roles restringidos. */
    public const POR_DEFECTO = [
        'modelo' => [],
        'moderador' => ['reportes'],
    ];

    /**
     * Módulos a los que puede entrar un rol.
     *
     * @param  array<int, string>|null  $permisos  lista guardada en `roles.permisos` (null = nunca editada)
     * @return array<int, string>
     */
    public static function modulosDe(?string $nombreRol, ?array $permisos = null): array
    {
        $nombreRol = strtolower(trim((string) $nombreRol));
        $todos = array_keys(self::MODULOS);

        if (in_array($nombreRol, self::ROLES_BLOQUEADOS, true)) {
            return $todos;
        }

        if ($permisos !== null) {
            return array_values(array_intersect($todos, $permisos));
        }

        return self::POR_DEFECTO[$nombreRol] ?? $todos;
    }

    public static function esBloqueado(?string $nombreRol): bool
    {
        return in_array(strtolower(trim((string) $nombreRol)), self::ROLES_BLOQUEADOS, true);
    }

    /** @return array<int, string> */
    public static function todos(): array
    {
        return array_keys(self::MODULOS);
    }
}
