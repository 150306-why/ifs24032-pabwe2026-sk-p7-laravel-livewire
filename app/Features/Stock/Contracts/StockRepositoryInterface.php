<?php

namespace App\Features\Stock\Contracts;

use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StockRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;

    public function allForUser(int $userId): Collection;

    public function findById(int $id): ?Stock;

    public function create(array $data): Stock;

    public function update(Stock $stock, array $data): Stock;

    public function delete(Stock $stock): void;

    /**
     * @return array{total_items:int,total_quantity:int,low_stock:int,out_of_stock:int}
     */
    public function statsForUser(int $userId): array;
}
