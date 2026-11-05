<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(['username' => 'admin'], [
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => 'admin',
            'role' => User::ROLE_ADMIN,
        ]);

        $member = User::query()->updateOrCreate(['username' => 'member'], [
            'name' => 'Member Demo',
            'email' => 'member@example.com',
            'password' => 'member',
            'role' => User::ROLE_MEMBER,
        ]);

        $samples = [
            ['Beras Premium 5kg', 'BRS-001', 120, 'karung', 68000, 20],
            ['Minyak Goreng 2L', 'MYK-002', 4, 'botol', 38000, 10],
            ['Gula Pasir 1kg', 'GLA-003', 0, 'kg', 17000, 10],
            ['Kopi Bubuk 200g', 'KPI-004', 45, 'pack', 25000, 10],
            ['Teh Celup Isi 25', 'TEH-005', 8, 'kotak', 9500, 10],
        ];

        foreach ($samples as [$name, $sku, $qty, $unit, $price, $min]) {
            Stock::query()->updateOrCreate(
                ['user_id' => $member->id, 'sku' => $sku],
                [
                    'name' => $name,
                    'description' => "**{$name}** — contoh data awal.",
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price' => $price,
                    'min_stock' => $min,
                    'status' => StockStatus::fromQuantity($qty, $min),
                ],
            );
        }
    }
}
