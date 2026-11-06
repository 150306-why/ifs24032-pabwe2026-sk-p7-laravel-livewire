<?php

namespace Tests\Unit\Enums;

use App\Features\Stock\Enums\StockStatus;
use PHPUnit\Framework\TestCase;

class StockStatusTest extends TestCase
{
    public function test_values_and_cases(): void
    {
        $this->assertSame(['available', 'low_stock', 'out_of_stock'], StockStatus::values());
        $this->assertCount(3, StockStatus::cases());
    }

    public function test_labels(): void
    {
        $this->assertSame('Tersedia', StockStatus::AVAILABLE->label());
        $this->assertSame('Stok Menipis', StockStatus::LOW_STOCK->label());
        $this->assertSame('Stok Habis', StockStatus::OUT_OF_STOCK->label());
    }

    public function test_badge_classes_are_distinct(): void
    {
        $classes = array_map(fn (StockStatus $s) => $s->badgeClass(), StockStatus::cases());

        $this->assertCount(3, array_unique($classes));
        $this->assertStringContainsString('green', StockStatus::AVAILABLE->badgeClass());
        $this->assertStringContainsString('yellow', StockStatus::LOW_STOCK->badgeClass());
        $this->assertStringContainsString('red', StockStatus::OUT_OF_STOCK->badgeClass());
    }
}
