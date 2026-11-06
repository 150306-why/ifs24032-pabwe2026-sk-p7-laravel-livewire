<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman READ-ONLY: tidak ada aksi ubah/hapus stok pengguna lain.
 */
#[Layout('components.layouts.app')]
#[Title('Stok Pengguna')]
class AdminUserStockList extends Component
{
    use WithPagination;

    #[Locked]
    public User $user;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(AdminUserService $service): View
    {
        return view('livewire.admin.admin-user-stock-list', [
            'stocks' => $service->stocksOf($this->user, $this->search ?: null, $this->statusFilter ?: null),
            'stats' => $service->summaryOf($this->user),
            'statuses' => StockStatus::cases(),
        ]);
    }
}
