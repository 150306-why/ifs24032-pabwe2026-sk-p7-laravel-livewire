<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\Forms\StockForm;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Inventaris')]
class StockList extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public StockForm $form;

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    #[Locked]
    public string $deletingName = '';

    // Import Excel
    public bool $showImport = false;

    public $importFile = null;

    public array $importResult = [];

    // AI Generator
    public bool $showAi = false;

    public string $aiPrompt = '';

    public int $aiCount = 5;

    #[Locked]
    public array $aiItems = [];

    public array $aiSelected = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Stock::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ---------- CRUD ----------

    public function openCreate(): void
    {
        $this->authorize('create', Stock::class);

        $this->form->reset();
        $this->resetValidation();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function openEdit(int $id, StockService $service): void
    {
        $stock = $service->findOrFail($id);
        $this->authorize('update', $stock);

        $this->resetValidation();
        $this->form->fillFromStock($stock);
        $this->editingId = $stock->id;
        $this->showForm = true;
    }

    public function save(StockService $service): void
    {
        $data = $this->form->validate();

        if ($this->editingId !== null) {
            $stock = $service->findOrFail($this->editingId);
            $this->authorize('update', $stock);
            $service->update($stock, $data);
            $message = 'Barang berhasil diperbarui.';
        } else {
            $this->authorize('create', Stock::class);
            $service->create(Auth::user(), $data);
            $message = 'Barang berhasil ditambahkan.';
        }

        $this->closeForm();
        session()->flash('status', $message);
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->form->reset();
        $this->resetValidation();
    }

    public function confirmDelete(int $id, StockService $service): void
    {
        $stock = $service->findOrFail($id);
        $this->authorize('delete', $stock);

        $this->deletingId = $stock->id;
        $this->deletingName = $stock->name;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
        $this->deletingName = '';
    }

    public function delete(StockService $service): void
    {
        $stock = $service->findOrFail((int) $this->deletingId);
        $this->authorize('delete', $stock);

        $service->delete($stock);
        $this->cancelDelete();

        session()->flash('status', 'Barang berhasil dihapus.');
    }

    public function adjust(int $id, int $delta, StockService $service): void
    {
        $stock = $service->findOrFail($id);
        $this->authorize('update', $stock);

        try {
            $service->adjustQuantity($stock, $delta);
            session()->flash('status', 'Quantity berhasil diperbarui.');
        } catch (InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ---------- EXCEL ----------

    public function openImport(): void
    {
        $this->reset('importFile', 'importResult');
        $this->resetValidation();
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->showImport = false;
        $this->reset('importFile', 'importResult');
        $this->resetValidation();
    }

    public function export(StockExcelService $excel): StreamedResponse
    {
        $content = $excel->export(Auth::user());

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'stock-things-'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(StockExcelService $excel): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'extensions:xlsx,xls', 'max:2048'],
        ]);

        try {
            $this->importResult = $excel->import(Auth::user(), $this->importFile->getRealPath());
        } catch (InvalidArgumentException $e) {
            $this->addError('importFile', $e->getMessage());

            return;
        }

        $this->reset('importFile');
    }

    // ---------- AI GENERATOR ----------

    public function openAi(): void
    {
        $this->reset('aiPrompt', 'aiItems', 'aiSelected');
        $this->aiCount = 5;
        $this->resetValidation();
        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->showAi = false;
        $this->reset('aiPrompt', 'aiItems', 'aiSelected');
        $this->resetValidation();
    }

    public function generateAi(AiClientInterface $ai, StockService $service): void
    {
        $this->validate([
            'aiPrompt' => ['required', 'string', 'min:3', 'max:500'],
            'aiCount' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $items = $ai->generate($this->aiPrompt, $this->aiCount);
        } catch (RuntimeException $e) {
            $this->addError('aiPrompt', $e->getMessage());

            return;
        }

        $this->aiSelected = [];
        $this->aiItems = array_map(function (GeneratedStockItem $item) use ($service) {
            $row = $item->toArray();
            $row['status'] = $service->resolveStatus($item->quantity, $item->minStock)->value;

            return $row;
        }, $items);
    }

    public function selectAllAi(): void
    {
        $this->aiSelected = array_map('strval', array_keys($this->aiItems));
    }

    public function saveAi(StockService $service): void
    {
        $saved = 0;

        foreach (array_unique(array_map('intval', $this->aiSelected)) as $index) {
            $item = $this->aiItems[$index] ?? null;

            // Validasi ulang: jangan percaya data dari browser.
            if ($item === null || Validator::make($item, GeneratedStockItem::rules())->fails()) {
                continue;
            }

            $service->create(Auth::user(), $item);
            $saved++;
        }

        if ($saved === 0) {
            $this->addError('aiSelected', 'Pilih minimal satu item yang valid untuk disimpan.');

            return;
        }

        $this->closeAi();
        session()->flash('status', "{$saved} barang dari AI berhasil disimpan.");
    }

    public function render(StockService $service): View
    {
        $user = Auth::user();

        return view('livewire.stock.stock-list', [
            'stocks' => $service->list($user, $this->search ?: null, $this->statusFilter ?: null),
            'stats' => $service->stats($user),
            'statuses' => StockStatus::cases(),
        ]);
    }
}
