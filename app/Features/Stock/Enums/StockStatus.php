<?php

namespace App\Features\Stock\Enums;

enum StockStatus: string
{
    case AVAILABLE = 'available';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::LOW_STOCK => 'Stok Menipis',
            self::OUT_OF_STOCK => 'Stok Habis',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20',
            self::LOW_STOCK => 'bg-yellow-100 text-yellow-800 ring-1 ring-inset ring-yellow-600/20',
            self::OUT_OF_STOCK => 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-600/20',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
