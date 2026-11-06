<?php

namespace Database\Factories;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(6, 100);

        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit' => 'pcs',
            'price' => fake()->randomFloat(2, 1000, 500000),
            'min_stock' => 5,
            'status' => StockStatus::AVAILABLE,
            'cover_image_path' => null,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['quantity' => 3, 'min_stock' => 5, 'status' => StockStatus::LOW_STOCK]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['quantity' => 0, 'min_stock' => 5, 'status' => StockStatus::OUT_OF_STOCK]);
    }
}
