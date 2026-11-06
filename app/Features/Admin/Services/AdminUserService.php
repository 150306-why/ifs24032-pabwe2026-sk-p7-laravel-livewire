<?php

namespace App\Features\Admin\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    /**
     * Daftar seluruh pengguna beserta ringkasan stok (READ-ONLY).
     */
    public function users(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->withCount('stocks')
            ->withSum('stocks', 'quantity')
            ->when($search, function ($query, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function stocksOf(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user->id, $search, $status, $perPage);
    }

    /**
     * @return array{total_items:int,total_quantity:int,low_stock:int,out_of_stock:int}
     */
    public function summaryOf(User $user): array
    {
        return $this->repository->statsForUser($user->id);
    }
}
