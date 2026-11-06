<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_from_array_with_defaults(): void
    {
        $item = GeneratedStockItem::fromArray(['name' => ' Kabel ', 'quantity' => '4']);

        $this->assertSame('Kabel', $item->name);
        $this->assertNull($item->sku);
        $this->assertNull($item->description);
        $this->assertSame(4, $item->quantity);
        $this->assertSame('pcs', $item->unit);
        $this->assertNull($item->price);
        $this->assertSame(5, $item->minStock);
        $this->assertSame(StockStatus::AVAILABLE, $item->status);
    }

    public function test_from_array_full_and_to_array(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => 'Mouse', 'sku' => 'MS-1', 'description' => 'Mouse **wireless**',
            'quantity' => 2, 'unit' => 'unit', 'price' => '125000', 'min_stock' => 3, 'status' => 'low_stock',
        ]);

        $this->assertSame([
            'name' => 'Mouse', 'sku' => 'MS-1', 'description' => 'Mouse **wireless**',
            'quantity' => 2, 'unit' => 'unit', 'price' => 125000.0, 'min_stock' => 3, 'status' => 'low_stock',
        ], $item->toArray());
    }

    public function test_unknown_status_falls_back_and_rules_exist(): void
    {
        $item = GeneratedStockItem::fromArray(['name' => 'A', 'quantity' => 1, 'status' => 'ngawur', 'price' => '']);

        $this->assertSame(StockStatus::AVAILABLE, $item->status);
        $this->assertNull($item->price);
        $this->assertArrayHasKey('name', GeneratedStockItem::rules());
        $this->assertArrayHasKey('quantity', GeneratedStockItem::rules());
    }
}
