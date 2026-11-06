<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class StockListTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->member = User::factory()->create();
    }

    private function component()
    {
        return Livewire::actingAs($this->member)->test(StockList::class);
    }

    private function xlsxUpload(array $rows, string $name = 'stocks.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $path = sys_get_temp_dir().'/'.uniqid('up_', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function aiItem(string $name, int $qty = 10, int $min = 5): GeneratedStockItem
    {
        return new GeneratedStockItem($name, strtoupper(substr($name, 0, 3)).'-1', 'Deskripsi', $qty, 'pcs', 1000.0, $min, StockStatus::AVAILABLE);
    }

    private function stubAi(array|\Throwable $result): void
    {
        $stub = $this->createStub(AiClientInterface::class);

        if ($result instanceof \Throwable) {
            $stub->method('generate')->willThrowException($result);
        } else {
            $stub->method('generate')->willReturn($result);
        }

        $this->app->instance(AiClientInterface::class, $stub);
    }

    // ---------- akses & daftar ----------

    public function test_admin_cannot_use_the_stock_list(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(StockList::class)->assertForbidden();
    }

    public function test_lists_only_own_stocks_with_summary(): void
    {
        Stock::factory()->create(['user_id' => $this->member->id, 'name' => 'Milik Saya']);
        Stock::factory()->create(['name' => 'Milik Orang Lain']);

        $this->component()->assertSee('Milik Saya')->assertDontSee('Milik Orang Lain');
    }

    public function test_empty_state(): void
    {
        $this->component()->assertSee('Belum ada barang');
    }

    public function test_search_and_status_filter(): void
    {
        Stock::factory()->create(['user_id' => $this->member->id, 'name' => 'Pensil', 'sku' => 'PN-1']);
        Stock::factory()->outOfStock()->create(['user_id' => $this->member->id, 'name' => 'Buku', 'sku' => 'BK-1']);

        $this->component()
            ->set('search', 'PN-1')->assertSee('Pensil')->assertDontSee('Buku')
            ->set('search', '')
            ->set('statusFilter', 'out_of_stock')->assertSee('Buku')->assertDontSee('Pensil');
    }

    // ---------- CRUD ----------

    public function test_create_stock(): void
    {
        $this->component()
            ->call('openCreate')->assertSet('showForm', true)
            ->set('form.name', 'Pensil 2B')
            ->set('form.sku', 'PN-2B')
            ->set('form.description', 'Pensil **kayu**')
            ->set('form.quantity', 3)
            ->set('form.unit', 'pcs')
            ->set('form.price', '2500')
            ->set('form.min_stock', 5)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSee('Barang berhasil ditambahkan.');

        $stock = Stock::where('name', 'Pensil 2B')->first();
        $this->assertSame($this->member->id, $stock->user_id);
        $this->assertSame(StockStatus::LOW_STOCK, $stock->status);
    }

    public function test_create_validation_errors(): void
    {
        $this->component()
            ->call('openCreate')
            ->set('form.name', '')
            ->set('form.quantity', -1)
            ->set('form.min_stock', 'abc')
            ->set('form.price', -5)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.quantity', 'form.min_stock', 'form.price']);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_status_preview_is_rendered_in_modal(): void
    {
        $this->component()->call('openCreate')->set('form.quantity', 0)->assertSee('Stok Habis');
    }

    public function test_edit_stock(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->member->id, 'name' => 'Lama', 'quantity' => 20, 'price' => null, 'sku' => null, 'description' => null]);

        $this->component()
            ->call('openEdit', $stock->id)
            ->assertSet('editingId', $stock->id)
            ->assertSet('form.name', 'Lama')
            ->set('form.name', 'Baru')
            ->set('form.min_stock', 25)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Barang berhasil diperbarui.');

        $fresh = $stock->fresh();
        $this->assertSame('Baru', $fresh->name);
        $this->assertSame(StockStatus::LOW_STOCK, $fresh->status);
    }

    public function test_cannot_edit_adjust_or_delete_other_members_stock(): void
    {
        $foreign = Stock::factory()->create(['name' => 'Bukan Milik Saya', 'quantity' => 10]);

        $this->component()->call('openEdit', $foreign->id)->assertForbidden();
        $this->component()->call('adjust', $foreign->id, 5)->assertForbidden();
        $this->component()->call('confirmDelete', $foreign->id)->assertForbidden();

        $this->assertSame(10, $foreign->fresh()->quantity);
        $this->assertDatabaseHas('stocks', ['id' => $foreign->id]);
    }

    public function test_close_form_resets_state(): void
    {
        $this->component()
            ->call('openCreate')
            ->set('form.name', 'X')
            ->call('closeForm')
            ->assertSet('showForm', false)
            ->assertSet('form.name', '');
    }

    public function test_delete_flow(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->member->id, 'name' => 'Hapus Saya']);

        $this->component()
            ->call('confirmDelete', $stock->id)
            ->assertSet('deletingId', $stock->id)
            ->assertSet('deletingName', 'Hapus Saya')
            ->call('cancelDelete')
            ->assertSet('deletingId', null)
            ->call('confirmDelete', $stock->id)
            ->call('delete')
            ->assertSee('Barang berhasil dihapus.');

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_adjust_quantity(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->member->id, 'quantity' => 10, 'min_stock' => 5]);

        $this->component()->call('adjust', $stock->id, 5)->assertSee('Quantity berhasil diperbarui.');
        $this->assertSame(15, $stock->fresh()->quantity);

        $this->component()->call('adjust', $stock->id, -3);
        $this->assertSame(12, $stock->fresh()->quantity);
    }

    public function test_adjust_below_zero_is_rejected_with_message(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->member->id, 'quantity' => 1]);

        $this->component()
            ->call('adjust', $stock->id, -2)
            ->assertSee('Quantity tidak boleh kurang dari 0.');

        $this->assertSame(1, $stock->fresh()->quantity);
    }

    // ---------- Excel ----------

    public function test_export_downloads_a_file(): void
    {
        Stock::factory()->create(['user_id' => $this->member->id]);

        $this->component()->call('export')->assertFileDownloaded();
    }

    public function test_import_success_and_partial_failure(): void
    {
        $file = $this->xlsxUpload([
            ['name', 'sku', 'qty', 'unit', 'price', 'min_stock', 'status'],
            ['Pensil', 'PN-1', 10, 'pcs', 2500, 5, 'available'],
            ['', 'X', 1, 'pcs', 1, 5, 'available'],
        ]);

        $this->component()
            ->call('openImport')->assertSet('showImport', true)
            ->set('importFile', $file)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('importResult.imported', 1)
            ->assertSet('importResult.failed', 1)
            ->assertSee('baris berhasil diimpor')
            ->call('closeImport')
            ->assertSet('showImport', false);

        $this->assertDatabaseHas('stocks', ['name' => 'Pensil', 'user_id' => $this->member->id]);
    }

    public function test_import_with_invalid_header_shows_error(): void
    {
        $this->component()
            ->set('importFile', $this->xlsxUpload([['foo', 'bar'], [1, 2]]))
            ->call('import')
            ->assertHasErrors(['importFile']);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_import_validates_file_presence_and_extension(): void
    {
        $this->component()->call('import')->assertHasErrors(['importFile' => 'required']);

        $this->component()
            ->set('importFile', UploadedFile::fake()->create('data.pdf', 10))
            ->call('import')
            ->assertHasErrors(['importFile']);
    }

    // ---------- AI ----------

    public function test_ai_generate_preview_and_save_only_selected_items(): void
    {
        $this->stubAi([$this->aiItem('Kabel USB'), $this->aiItem('Mouse', 2, 5), $this->aiItem('Keyboard')]);

        $this->component()
            ->call('openAi')->assertSet('showAi', true)
            ->set('aiPrompt', 'perlengkapan komputer')
            ->set('aiCount', 3)
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertSee('Kabel USB')
            ->assertSee('Mouse')
            ->assertSet('aiItems.1.status', 'low_stock')
            ->set('aiSelected', ['0', '1'])
            ->call('saveAi')
            ->assertHasNoErrors()
            ->assertSet('showAi', false)
            ->assertSee('2 barang dari AI berhasil disimpan.');

        $this->assertDatabaseHas('stocks', ['name' => 'Kabel USB', 'user_id' => $this->member->id]);
        $this->assertDatabaseHas('stocks', ['name' => 'Mouse', 'status' => 'low_stock']);
        $this->assertDatabaseMissing('stocks', ['name' => 'Keyboard']);
    }

    public function test_ai_nothing_is_saved_without_selection(): void
    {
        $this->stubAi([$this->aiItem('Kabel USB')]);

        $this->component()
            ->set('aiPrompt', 'kabel')
            ->call('generateAi')
            ->call('saveAi')
            ->assertHasErrors(['aiSelected']);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_ai_select_all(): void
    {
        $this->stubAi([$this->aiItem('A1'), $this->aiItem('B2')]);

        $this->component()
            ->set('aiPrompt', 'abc')
            ->call('generateAi')
            ->call('selectAllAi')
            ->assertSet('aiSelected', ['0', '1'])
            ->call('saveAi');

        $this->assertDatabaseCount('stocks', 2);
    }

    public function test_ai_invalid_item_and_unknown_index_are_skipped(): void
    {
        $invalid = new GeneratedStockItem('', null, null, 1, 'pcs', null, 5, StockStatus::AVAILABLE);
        $this->stubAi([$invalid]);

        $this->component()
            ->set('aiPrompt', 'abc')
            ->call('generateAi')
            ->set('aiSelected', ['0', '99'])
            ->call('saveAi')
            ->assertHasErrors(['aiSelected']);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_ai_failure_is_reported(): void
    {
        $this->stubAi(new RuntimeException('Layanan AI sedang bermasalah.'));

        $this->component()
            ->set('aiPrompt', 'abc')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt'])
            ->assertSee('Layanan AI sedang bermasalah.');
    }

    public function test_ai_validation_and_close(): void
    {
        $this->component()
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt' => 'required']);

        $this->component()
            ->set('aiPrompt', 'abc')
            ->set('aiCount', 50)
            ->call('generateAi')
            ->assertHasErrors(['aiCount']);

        $this->component()
            ->call('openAi')
            ->set('aiPrompt', 'abc')
            ->call('closeAi')
            ->assertSet('showAi', false)
            ->assertSet('aiPrompt', '');
    }
}
