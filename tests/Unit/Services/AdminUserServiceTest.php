<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminUserService(new StockRepository);
    }

    public function test_users_include_stock_summary(): void
    {
        $budi = User::factory()->create(['name' => 'Budi', 'username' => 'budi', 'email' => 'budi@x.test']);
        User::factory()->create(['name' => 'Ani', 'username' => 'ani', 'email' => 'ani@x.test']);
        Stock::factory()->create(['user_id' => $budi->id, 'quantity' => 10]);
        Stock::factory()->create(['user_id' => $budi->id, 'quantity' => 5]);

        $users = $this->service->users();

        $this->assertSame(2, $users->total());
        $row = $users->firstWhere('username', 'budi');
        $this->assertSame(2, $row->stocks_count);
        $this->assertSame(15, (int) $row->stocks_sum_quantity);
    }

    public function test_users_search_by_name_username_and_email(): void
    {
        User::factory()->create(['name' => 'Budi', 'username' => 'budi', 'email' => 'b@x.test']);
        User::factory()->create(['name' => 'Ani', 'username' => 'anita', 'email' => 'ani@mail.test']);

        $this->assertSame(1, $this->service->users('Budi')->total());
        $this->assertSame(1, $this->service->users('anita')->total());
        $this->assertSame(1, $this->service->users('mail.test')->total());
        $this->assertSame(0, $this->service->users('tidak-ada')->total());
    }

    public function test_stocks_of_and_summary_are_scoped_to_target_user(): void
    {
        $target = User::factory()->create();
        Stock::factory()->create(['user_id' => $target->id, 'name' => 'Pensil', 'quantity' => 10]);
        Stock::factory()->outOfStock()->create(['user_id' => $target->id, 'name' => 'Buku']);
        Stock::factory()->create(['name' => 'Bukan Target']);

        $this->assertSame(2, $this->service->stocksOf($target)->total());
        $this->assertSame(1, $this->service->stocksOf($target, 'Pensil')->total());
        $this->assertSame(1, $this->service->stocksOf($target, null, 'out_of_stock')->total());

        $summary = $this->service->summaryOf($target);
        $this->assertSame(2, $summary['total_items']);
        $this->assertSame(10, $summary['total_quantity']);
        $this->assertSame(1, $summary['out_of_stock']);
    }
}
