<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_user_and_casts(): void
    {
        $stock = Stock::factory()->create(['price' => 1234.5, 'quantity' => '7']);

        $this->assertInstanceOf(User::class, $stock->user);
        $this->assertSame(7, $stock->quantity);
        $this->assertSame('1234.50', $stock->price);
        $this->assertSame(StockStatus::AVAILABLE, $stock->status);
    }

    public function test_cover_url(): void
    {
        $stock = Stock::factory()->create();
        $this->assertNull($stock->coverUrl());

        $stock->cover_image_path = 'stock-covers/x.jpg';
        $this->assertStringContainsString('stock-covers/x.jpg', $stock->coverUrl());
    }

    public function test_factory_states(): void
    {
        $this->assertSame(StockStatus::LOW_STOCK, Stock::factory()->lowStock()->create()->status);
        $this->assertSame(StockStatus::OUT_OF_STOCK, Stock::factory()->outOfStock()->create()->status);
    }

    public function test_deleting_user_cascades_to_stocks(): void
    {
        $stock = Stock::factory()->create();
        $stock->user->delete();

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
