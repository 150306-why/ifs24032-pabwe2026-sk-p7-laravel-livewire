<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Stock::query()
            ->where('user_id', $userId)
            ->when($search, function ($query, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)->orWhere('sku', 'like', $like);
                });
            })
            ->when($status, fn ($query, string $value) => $query->where('status', $value))
            ->latest('id')
            ->paginate($perPage);
    }

    public function allForUser(int $userId): Collection
    {
        return Stock::query()->where('user_id', $userId)->orderBy('name')->get();
    }

    public function findById(int $id): ?Stock
    {
        return Stock::query()->find($id);
    }

    public function create(array $data): Stock
    {
        return Stock::query()->create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->update($data);

        return $stock->refresh();
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }

    public function statsForUser(int $userId): array
    {
        $base = Stock::query()->where('user_id', $userId);

        return [
            'total_items' => (clone $base)->count(),
            'total_quantity' => (int) (clone $base)->sum('quantity'),
            'low_stock' => (clone $base)->where('status', StockStatus::LOW_STOCK->value)->count(),
            'out_of_stock' => (clone $base)->where('status', StockStatus::OUT_OF_STOCK->value)->count(),
        ];
    }
}
