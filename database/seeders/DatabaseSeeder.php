<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use App\Models\Rol;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = ['Moderador', 'Modelo'];

        foreach ($roles as $index => $rol) {
            Rol::updateOrCreate(['id_rol' => $index + 1], ['rol' => $rol]);
        }

        $metodos = [
            ['metodo_pago' => 'PayPal', 'impuesto' => 0, 'porcentaje_cuenta' => 0],
            ['metodo_pago' => 'Zelle', 'impuesto' => 0, 'porcentaje_cuenta' => 0],
            ['metodo_pago' => 'Criptomoneda', 'impuesto' => 0, 'porcentaje_cuenta' => 0],
            ['metodo_pago' => 'Binance', 'impuesto' => 0, 'porcentaje_cuenta' => 0],
            ['metodo_pago' => 'Transferencia', 'impuesto' => 0, 'porcentaje_cuenta' => 0],
        ];

        foreach ($metodos as $index => $metodo) {
            MetodoPago::updateOrCreate(['id_mp' => $index + 1], $metodo);
        }
    }
}
