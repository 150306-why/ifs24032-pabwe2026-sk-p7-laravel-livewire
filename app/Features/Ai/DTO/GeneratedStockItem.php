<?php

namespace App\Features\Ai\DTO;

use App\Features\Stock\Enums\StockStatus;

final class GeneratedStockItem
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $sku,
        public readonly ?string $description,
        public readonly int $quantity,
        public readonly string $unit,
        public readonly ?float $price,
        public readonly int $minStock,
        public readonly StockStatus $status,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:999999999'],
            'unit' => ['nullable', 'string', 'max:20'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'min_stock' => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ];
    }

    public static function fromArray(array $data): self
    {
        $unit = trim((string) ($data['unit'] ?? ''));
        $sku = trim((string) ($data['sku'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $price = $data['price'] ?? null;

        return new self(
            name: trim((string) $data['name']),
            sku: $sku === '' ? null : $sku,
            description: $description === '' ? null : $description,
            quantity: (int) $data['quantity'],
            unit: $unit === '' ? 'pcs' : $unit,
            price: ($price === null || $price === '') ? null : (float) $price,
            minStock: isset($data['min_stock']) ? (int) $data['min_stock'] : 5,
            status: StockStatus::tryFrom((string) ($data['status'] ?? '')) ?? StockStatus::AVAILABLE,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
            'status' => $this->status->value,
        ];
    }
}
