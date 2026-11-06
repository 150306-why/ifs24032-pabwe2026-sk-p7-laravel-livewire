<?php

namespace App\Features\Stock\Livewire\Forms;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Livewire\Form;

class StockForm extends Form
{
    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public string|int|null $quantity = 0;

    public string $unit = 'pcs';

    public string|int|float|null $price = '';

    public string|int|null $min_stock = 5;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:20000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:999999999'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:999999999'],
        ];
    }

    public function fillFromStock(Stock $stock): void
    {
        $this->name = $stock->name;
        $this->sku = $stock->sku ?? '';
        $this->description = $stock->description ?? '';
        $this->quantity = $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price ?? '';
        $this->min_stock = $stock->min_stock;
    }

    /**
     * Pratinjau status otomatis untuk ditampilkan pada modal (read-only).
     */
    public function previewStatus(): StockStatus
    {
        return app(StockService::class)->resolveStatus((int) $this->quantity, (int) $this->min_stock);
    }
}
