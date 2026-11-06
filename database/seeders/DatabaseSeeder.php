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
        // Akun pengembangan. Password di-hash otomatis oleh cast "hashed" pada model User.
        User::updateOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'email' => 'admin@stockthings.test', 'role' => User::ROLE_ADMIN, 'password' => 'admin']
        );

        $member = User::updateOrCreate(
            ['username' => 'member'],
            ['name' => 'Member Demo', 'email' => 'member@stockthings.test', 'role' => User::ROLE_MEMBER, 'password' => 'member']
        );

        $samples = [
            ['Kertas A4 80gsm', 'ATK-001', 120, 'rim', 55000, 20],
            ['Pulpen Gel Hitam', 'ATK-002', 4, 'pcs', 4500, 10],
            ['Spidol Whiteboard', 'ATK-003', 0, 'pcs', 9000, 5],
            ['Map Plastik', 'ATK-004', 80, 'pcs', 2500, 15],
            ['Stapler Besar', 'ATK-005', 8, 'pcs', 35000, 5],
            ['Tinta Printer Hitam', 'PRN-001', 3, 'botol', 85000, 5],
        ];

        foreach ($samples as [$name, $sku, $qty, $unit, $price, $min]) {
            Stock::updateOrCreate(
                ['user_id' => $member->id, 'sku' => $sku],
                [
                    'name' => $name,
                    'description' => "Contoh data **{$name}** untuk pengembangan.",
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price' => $price,
                    'min_stock' => $min,
                    'status' => $qty === 0 ? StockStatus::OUT_OF_STOCK : ($qty <= $min ? StockStatus::LOW_STOCK : StockStatus::AVAILABLE),
                ]
            );
        }
    }
}
