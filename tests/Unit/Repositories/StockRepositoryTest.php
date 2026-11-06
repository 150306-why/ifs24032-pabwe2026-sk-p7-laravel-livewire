<?php

namespace Tests\Unit\Repositories;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private StockRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StockRepository;
    }

    public function test_paginate_only_returns_user_stocks_with_search_and_filter(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Pensil 2B', 'sku' => 'PN-1']);
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Buku', 'sku' => 'BK-9']);
        Stock::factory()->outOfStock()->create(['user_id' => $user->id, 'name' => 'Penghapus', 'sku' => 'PH-1']);
        Stock::factory()->create(['user_id' => $other->id, 'name' => 'Pensil Orang Lain']);

        $this->assertSame(3, $this->repo->paginateForUser($user->id)->total());
        $this->assertSame(2, $this->repo->paginateForUser($user->id, 'Pen')->total());
        $this->assertSame(1, $this->repo->paginateForUser($user->id, 'BK-9')->total());
        $this->assertSame(1, $this->repo->paginateForUser($user->id, null, 'out_of_stock')->total());
        $this->assertSame(0, $this->repo->paginateForUser($user->id, '%')->total());
    }

    public function test_crud(): void
    {
        $user = User::factory()->create();

        $stock = $this->repo->create(['user_id' => $user->id, 'name' => 'A', 'quantity' => 1]);
        $this->assertSame($stock->id, $this->repo->findById($stock->id)->id);
        $this->assertNull($this->repo->findById(999999));

        $updated = $this->repo->update($stock, ['name' => 'B']);
        $this->assertSame('B', $updated->name);

        $this->repo->delete($stock);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_all_for_user_is_scoped_and_sorted(): void
    {
        $user = User::factory()->create();
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Zebra']);
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Apel']);
        Stock::factory()->create(['name' => 'Milik Orang Lain']);

        $names = $this->repo->allForUser($user->id)->pluck('name')->all();

        $this->assertSame(['Apel', 'Zebra'], $names);
    }

    public function test_stats(): void
    {
        $user = User::factory()->create();
        Stock::factory()->create(['user_id' => $user->id, 'quantity' => 10]);
        Stock::factory()->lowStock()->create(['user_id' => $user->id]);
        Stock::factory()->outOfStock()->create(['user_id' => $user->id]);
        Stock::factory()->create(['quantity' => 500]);

        $this->assertSame([
            'total_items' => 3,
            'total_quantity' => 13,
            'low_stock' => 1,
            'out_of_stock' => 1,
        ], $this->repo->statsForUser($user->id));
        $this->assertSame(StockStatus::LOW_STOCK->value, 'low_stock');
    }
}
