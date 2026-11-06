<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StockExcelService
{
    public const HEADERS = ['name', 'sku', 'qty', 'unit', 'price', 'min_stock', 'status'];

    public function __construct(
        private readonly StockRepositoryInterface $repository,
        private readonly StockService $stockService,
    ) {}

    /**
     * Mengekspor stok milik $user saja, mengembalikan isi file .xlsx.
     */
    public function export(User $user): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stocks');

        foreach (self::HEADERS as $index => $header) {
            $sheet->setCellValueExplicit([$index + 1, 1], $header, DataType::TYPE_STRING);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($this->repository->allForUser($user->id) as $stock) {
            // Teks ditulis eksplisit sebagai string agar nilai berawalan "=" tidak menjadi formula.
            $sheet->setCellValueExplicit([1, $row], (string) $stock->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([2, $row], (string) $stock->sku, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([3, $row], $stock->quantity, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit([4, $row], (string) $stock->unit, DataType::TYPE_STRING);
            if ($stock->price !== null) {
                $sheet->setCellValueExplicit([5, $row], (float) $stock->price, DataType::TYPE_NUMERIC);
            }
            $sheet->setCellValueExplicit([6, $row], $stock->min_stock, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit([7, $row], $stock->status->value, DataType::TYPE_STRING);
            $row++;
        }

        $path = tempnam(sys_get_temp_dir(), 'stk');
        (new Xlsx($spreadsheet))->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);
        $spreadsheet->disconnectWorksheets();

        return $content;
    }

    /**
     * Mengimpor file Excel ke stok milik $user.
     *
     * @return array{imported:int,failed:int,errors:list<string>}
     *
     * @throws InvalidArgumentException jika file tidak terbaca atau header salah
     */
    public function import(User $user, string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $rows = $reader->load($path)->getActiveSheet()->toArray(null, false, false, false);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('File Excel tidak dapat dibaca.');
        }

        $header = array_map(
            fn ($cell) => strtolower(trim((string) $cell)),
            array_shift($rows) ?? []
        );

        if (array_diff(self::HEADERS, $header) !== []) {
            throw new InvalidArgumentException(
                'Header tidak valid. Kolom wajib: '.implode(', ', self::HEADERS).'.'
            );
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $data = $this->mapRow($header, $row);

            if (collect($data)->filter(fn ($value) => $value !== null)->isEmpty()) {
                continue;
            }

            $line = $index + 2;
            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'sku' => ['nullable', 'string', 'max:100'],
                'qty' => ['required', 'integer', 'min:0', 'max:999999999'],
                'unit' => ['nullable', 'string', 'max:20'],
                'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'min_stock' => ['nullable', 'integer', 'min:0', 'max:999999999'],
                'status' => ['nullable', 'in:'.implode(',', StockStatus::values())],
            ]);

            if ($validator->fails()) {
                $errors[] = "Baris {$line}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            $this->stockService->create($user, [
                'name' => $data['name'],
                'sku' => $data['sku'],
                'quantity' => (int) $data['qty'],
                'unit' => $data['unit'] ?? 'pcs',
                'price' => $data['price'],
                'min_stock' => $data['min_stock'] ?? 5,
            ]);
            $imported++;
        }

        return [
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string>  $header
     * @return array<string, mixed>
     */
    private function mapRow(array $header, array $row): array
    {
        $data = [];

        foreach ($header as $column => $name) {
            $value = $row[$column] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $data[$name] = $value === '' ? null : $value;
        }

        foreach (['name', 'sku', 'unit', 'status'] as $textField) {
            if (is_int($data[$textField] ?? null) || is_float($data[$textField] ?? null)) {
                $data[$textField] = (string) $data[$textField];
            }
        }

        return $data;
    }
}
