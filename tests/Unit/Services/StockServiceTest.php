<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockService(new StockRepository);
        $this->user = User::factory()->create();
    }

    public function test_resolve_status_rules(): void
    {
        $this->assertSame(StockStatus::OUT_OF_STOCK, $this->service->resolveStatus(0, 5));
        $this->assertSame(StockStatus::LOW_STOCK, $this->service->resolveStatus(1, 5));
        $this->assertSame(StockStatus::LOW_STOCK, $this->service->resolveStatus(5, 5));
        $this->assertSame(StockStatus::AVAILABLE, $this->service->resolveStatus(6, 5));
    }

    public function test_create_normalizes_and_sets_status(): void
    {
        $stock = $this->service->create($this->user, [
            'name' => '  Pensil  ', 'sku' => '', 'description' => '', 'quantity' => '3',
            'unit' => '', 'price' => '', 'min_stock' => 5, 'status' => 'available',
        ]);

        $this->assertSame('Pensil', $stock->name);
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertNull($stock->price);
        $this->assertSame('pcs', $stock->unit);
        $this->assertSame(StockStatus::LOW_STOCK, $stock->status, 'input status harus diabaikan');
        $this->assertSame($this->user->id, $stock->user_id);
    }

    public function test_create_with_price_and_zero_quantity(): void
    {
        $stock = $this->service->create($this->user, ['name' => 'X', 'quantity' => 0, 'min_stock' => 5, 'price' => '1500.456', 'unit' => 'box', 'sku' => 'S1', 'description' => 'desc']);

        $this->assertSame(StockStatus::OUT_OF_STOCK, $stock->status);
        $this->assertSame('1500.46', $stock->price);
        $this->assertSame('box', $stock->unit);
        $this->assertSame('S1', $stock->sku);
    }

    public function test_negative_quantity_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->create($this->user, ['name' => 'X', 'quantity' => -1]);
    }

    public function test_update_recalculates_status_when_min_stock_changes(): void
    {
        $stock = $this->service->create($this->user, ['name' => 'X', 'quantity' => 10, 'min_stock' => 5]);
        $this->assertSame(StockStatus::AVAILABLE, $stock->status);

        $stock = $this->service->update($stock, ['name' => 'X2', 'quantity' => 10, 'min_stock' => 10]);

        $this->assertSame('X2', $stock->name);
        $this->assertSame(StockStatus::LOW_STOCK, $stock->status);
    }

    public function test_adjust_quantity_add_and_subtract(): void
    {
        $stock = $this->service->create($this->user, ['name' => 'X', 'quantity' => 10, 'min_stock' => 5]);

        $stock = $this->service->adjustQuantity($stock, 5);
        $this->assertSame(15, $stock->quantity);
        $this->assertSame(StockStatus::AVAILABLE, $stock->status);

        $stock = $this->service->adjustQuantity($stock, -3);
        $this->assertSame(12, $stock->quantity);

        $stock = $this->service->adjustQuantity($stock, -8);
        $this->assertSame(4, $stock->quantity);
        $this->assertSame(StockStatus::LOW_STOCK, $stock->status);

        $stock = $this->service->adjustQuantity($stock, -4);
        $this->assertSame(0, $stock->quantity);
        $this->assertSame(StockStatus::OUT_OF_STOCK, $stock->status);
    }

    public function test_adjust_quantity_cannot_go_below_zero(): void
    {
        $stock = $this->service->create($this->user, ['name' => 'X', 'quantity' => 2, 'min_stock' => 5]);

        try {
            $this->service->adjustQuantity($stock, -3);
            $this->fail('Seharusnya melempar exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Quantity tidak boleh kurang dari 0.', $e->getMessage());
        }

        $this->assertSame(2, $stock->fresh()->quantity);
    }

    public function test_find_or_fail(): void
    {
        $stock = $this->service->create($this->user, ['name' => 'X', 'quantity' => 1]);

        $this->assertSame($stock->id, $this->service->findOrFail($stock->id)->id);

        $this->expectException(ModelNotFoundException::class);
        $this->service->findOrFail(999999);
    }

    public function test_list_and_stats(): void
    {
        $this->service->create($this->user, ['name' => 'Pensil', 'quantity' => 10]);
        $this->service->create($this->user, ['name' => 'Buku', 'quantity' => 0]);

        $this->assertSame(1, $this->service->list($this->user, 'Pensil')->total());
        $this->assertSame(1, $this->service->list($this->user, null, 'out_of_stock')->total());
        $this->assertSame(2, $this->service->stats($this->user)['total_items']);
    }

    public function test_cover_upload_replace_and_remove(): void
    {
        Storage::fake('public');
        $stock = $this->service->create($this->user, ['name' => 'X', 'quantity' => 1]);

        $stock = $this->service->storeCover($stock, UploadedFile::fake()->image('a.jpg'));
        $first = $stock->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $stock = $this->service->storeCover($stock, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($stock->cover_image_path);

        $second = $stock->cover_image_path;
        $stock = $this->service->removeCover($stock);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($stock->cover_image_path);

        // removeCover tanpa cover tidak error
        $this->assertNull($this->service->removeCover($stock)->cover_image_path);
    }

    public function test_delete_removes_record_and_cover(): void
    {
        Storage::fake('public');
        $stock = $this->service->create($this->user, ['name' => 'X', 'quantity' => 1]);
        $stock = $this->service->storeCover($stock, UploadedFile::fake()->image('a.jpg'));
        $path = $stock->cover_image_path;

        $this->service->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
