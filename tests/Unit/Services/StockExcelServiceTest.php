<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockExcelService $service;

    private User $user;

    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();

        $repo = new StockRepository;
        $this->service = new StockExcelService($repo, new StockService($repo));
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    private function makeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = sys_get_temp_dir().'/'.uniqid('stk_', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $this->files[] = $path;
    }

    private const HEADER = ['name', 'sku', 'qty', 'unit', 'price', 'min_stock', 'status'];

    public function test_import_saves_valid_rows_and_reports_invalid_ones(): void
    {
        $path = $this->makeXlsx([
            self::HEADER,
            ['Pensil', 'PN-1', 10, 'pcs', 2500, 5, 'available'],
            [null, null, null, null, null, null, null],             // baris kosong -> dilewati
            ['', 'X-1', 1, 'pcs', 1, 5, 'available'],               // nama kosong
            ['Buku', 'BK-1', 'abc', 'pcs', 1, 5, 'available'],      // qty bukan angka
            ['Pena', 'PE-1', 3, 'pcs', 'mahal', 5, 'available'],    // harga bukan angka
            ['Spidol', 'SP-1', 3, 'pcs', 5000, 5, 'ngawur'],        // status tidak dikenal
            ['Penggaris', 'PG-1', 4, null, null, null, null],       // field opsional kosong -> default
            [12345, 'NUM-1', 0, 'pcs', 100, 2, null],               // nama numerik -> string
        ]);

        $result = $this->service->import($this->user, $path);

        $this->assertSame(3, $result['imported']);
        $this->assertSame(4, $result['failed']);
        $this->assertCount(4, $result['errors']);
        $this->assertStringContainsString('Baris 4', $result['errors'][0]);

        $pensil = Stock::where('name', 'Pensil')->first();
        $this->assertSame($this->user->id, $pensil->user_id);
        $this->assertSame(StockStatus::AVAILABLE, $pensil->status);

        $penggaris = Stock::where('name', 'Penggaris')->first();
        $this->assertSame('pcs', $penggaris->unit);
        $this->assertSame(5, $penggaris->min_stock);
        $this->assertSame(StockStatus::LOW_STOCK, $penggaris->status, 'status dihitung ulang, bukan dari file');

        $this->assertSame(StockStatus::OUT_OF_STOCK, Stock::where('name', '12345')->first()->status);
    }

    public function test_import_accepts_case_insensitive_headers(): void
    {
        $path = $this->makeXlsx([
            ['NAME', ' Sku ', 'QTY', 'Unit', 'PRICE', 'Min_Stock', 'Status'],
            ['Lem', 'LM-1', 7, 'pcs', 3000, 5, ''],
        ]);

        $this->assertSame(1, $this->service->import($this->user, $path)['imported']);
    }

    public function test_import_rejects_invalid_header(): void
    {
        $path = $this->makeXlsx([['name', 'qty'], ['A', 1]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header tidak valid');

        $this->service->import($this->user, $path);
    }

    public function test_import_rejects_empty_workbook(): void
    {
        $path = sys_get_temp_dir().'/'.uniqid('stk_', true).'.xlsx';
        (new Xlsx(new Spreadsheet))->save($path);
        $this->files[] = $path;

        $this->expectException(InvalidArgumentException::class);

        $this->service->import($this->user, $path);
    }

    public function test_import_rejects_unreadable_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak dapat dibaca');

        $this->service->import($this->user, '/tidak/ada/file.xlsx');
    }

    public function test_export_contains_only_the_users_stocks(): void
    {
        $other = User::factory()->create();
        Stock::factory()->create(['user_id' => $this->user->id, 'name' => 'Milik Saya', 'sku' => 'MS-1', 'quantity' => 12, 'price' => 2500]);
        Stock::factory()->create(['user_id' => $this->user->id, 'name' => '=1+1', 'sku' => null, 'price' => null]);
        Stock::factory()->create(['user_id' => $other->id, 'name' => 'Milik Orang Lain']);

        $path = sys_get_temp_dir().'/'.uniqid('stk_', true).'.xlsx';
        file_put_contents($path, $this->service->export($this->user));
        $this->files[] = $path;

        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertCount(3, $rows);

        $names = array_column($rows, 0);
        $this->assertContains('Milik Saya', $names);
        $this->assertContains('=1+1', $names, 'teks berawalan = harus tetap string, bukan formula');
        $this->assertNotContains('Milik Orang Lain', $names);

        $mine = $rows[array_search('Milik Saya', $names)];
        $this->assertSame(12, $mine[2]);
        $this->assertSame('available', $mine[6]);
    }

    public function test_export_of_empty_inventory_still_has_header(): void
    {
        $path = sys_get_temp_dir().'/'.uniqid('stk_', true).'.xlsx';
        file_put_contents($path, $this->service->export($this->user));
        $this->files[] = $path;

        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);

        $this->assertSame([StockExcelService::HEADERS], $rows);
    }
}
