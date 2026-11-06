<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    }

    public function test_user_list_shows_summary_and_supports_search(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso']);
        User::factory()->create(['name' => 'Citra Dewi']);
        Stock::factory()->create(['user_id' => $budi->id, 'quantity' => 40]);

        Livewire::actingAs($this->admin)->test(UserList::class)
            ->assertSee('Budi Santoso')
            ->assertSee('Citra Dewi')
            ->assertSee('Lihat stok')
            ->set('search', 'Citra')
            ->assertSee('Citra Dewi')
            ->assertDontSee('Budi Santoso');
    }

    public function test_user_list_empty_state(): void
    {
        Livewire::actingAs($this->admin)->test(UserList::class)
            ->set('search', 'tidak-ada-yang-cocok')
            ->assertSee('Tidak ada pengguna.');
    }

    public function test_user_stock_list_is_scoped_filterable_and_read_only(): void
    {
        $member = User::factory()->create(['name' => 'Budi']);
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Pensil Budi']);
        Stock::factory()->outOfStock()->create(['user_id' => $member->id, 'name' => 'Buku Budi']);
        Stock::factory()->create(['name' => 'Stok Orang Lain']);

        $component = Livewire::actingAs($this->admin)->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Pensil Budi')
            ->assertSee('Buku Budi')
            ->assertDontSee('Stok Orang Lain')
            ->assertSee('mode hanya baca')
            ->assertDontSee('Hapus')
            ->assertDontSee('Edit')
            ->set('search', 'Pensil')
            ->assertDontSee('Buku Budi')
            ->set('search', '')
            ->set('statusFilter', 'out_of_stock')
            ->assertSee('Buku Budi')
            ->assertDontSee('Pensil Budi');

        foreach (['save', 'delete', 'adjust', 'openEdit', 'confirmDelete'] as $method) {
            $this->assertFalse(method_exists($component->instance(), $method), "Admin tidak boleh punya aksi {$method}");
        }
    }

    public function test_user_stock_list_empty_state(): void
    {
        $member = User::factory()->create();

        Livewire::actingAs($this->admin)->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('belum memiliki barang');
    }
}
