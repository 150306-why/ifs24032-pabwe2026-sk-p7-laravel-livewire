<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class StockService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    public function list(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user->id, $search, $status, $perPage);
    }

    /**
     * @return array{total_items:int,total_quantity:int,low_stock:int,out_of_stock:int}
     */
    public function stats(User $user): array
    {
        return $this->repository->statsForUser($user->id);
    }

    public function findOrFail(int $id): Stock
    {
        $stock = $this->repository->findById($id);

        if ($stock === null) {
            throw (new ModelNotFoundException)->setModel(Stock::class, [$id]);
        }

        return $stock;
    }

    /**
     * Aturan status:
     *  - quantity = 0                 => OUT_OF_STOCK
     *  - 0 < quantity <= min_stock    => LOW_STOCK
     *  - quantity > min_stock         => AVAILABLE
     */
    public function resolveStatus(int $quantity, int $minStock): StockStatus
    {
        if ($quantity <= 0) {
            return StockStatus::OUT_OF_STOCK;
        }

        if ($quantity <= $minStock) {
            return StockStatus::LOW_STOCK;
        }

        return StockStatus::AVAILABLE;
    }

    public function create(User $user, array $data): Stock
    {
        $payload = $this->normalize($data);
        $payload['user_id'] = $user->id;

        return $this->repository->create($payload);
    }

    public function update(Stock $stock, array $data): Stock
    {
        return $this->repository->update($stock, $this->normalize($data));
    }

    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $newQuantity = $stock->quantity + $delta;

        if ($newQuantity < 0) {
            throw new InvalidArgumentException('Quantity tidak boleh kurang dari 0.');
        }

        return $this->repository->update($stock, [
            'quantity' => $newQuantity,
            'status' => $this->resolveStatus($newQuantity, $stock->min_stock),
        ]);
    }

    public function storeCover(Stock $stock, UploadedFile $file): Stock
    {
        $this->deleteCoverFile($stock);

        $path = $file->store('stock-covers', 'public');

        return $this->repository->update($stock, ['cover_image_path' => $path]);
    }

    public function removeCover(Stock $stock): Stock
    {
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => null]);
    }

    public function delete(Stock $stock): void
    {
        $this->deleteCoverFile($stock);
        $this->repository->delete($stock);
    }

    private function deleteCoverFile(Stock $stock): void
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }

    /**
     * Normalisasi input dan hitung status otomatis (input "status" dari luar diabaikan).
     */
    private function normalize(array $data): array
    {
        $quantity = (int) ($data['quantity'] ?? 0);

        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity tidak boleh negatif.');
        }

        $minStock = max(0, (int) ($data['min_stock'] ?? 5));
        $unit = trim((string) ($data['unit'] ?? ''));
        $sku = trim((string) ($data['sku'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $price = $data['price'] ?? null;

        return [
            'name' => trim((string) $data['name']),
            'sku' => $sku === '' ? null : $sku,
            'description' => $description === '' ? null : $description,
            'quantity' => $quantity,
            'unit' => $unit === '' ? 'pcs' : $unit,
            'price' => ($price === null || $price === '') ? null : round((float) $price, 2),
            'min_stock' => $minStock,
            'status' => $this->resolveStatus($quantity, $minStock),
        ];
    }
}
