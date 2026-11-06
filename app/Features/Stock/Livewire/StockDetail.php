<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Livewire\Forms\StockForm;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Barang')]
class StockDetail extends Component
{
    use AuthorizesRequests, WithFileUploads;

    #[Locked]
    public Stock $stock;

    public StockForm $form;

    public bool $showEdit = false;

    public bool $showDelete = false;

    public string|int $adjustAmount = 1;

    public $cover = null;

    public function mount(Stock $stock): void
    {
        $this->authorize('view', $stock);

        $this->stock = $stock;
    }

    public function openEdit(): void
    {
        $this->authorize('update', $this->stock);

        $this->resetValidation();
        $this->form->fillFromStock($this->stock);
        $this->showEdit = true;
    }

    public function closeEdit(): void
    {
        $this->showEdit = false;
        $this->form->reset();
        $this->resetValidation();
    }

    public function save(StockService $service): void
    {
        $this->authorize('update', $this->stock);

        $data = $this->form->validate();
        $this->stock = $service->update($this->stock, $data);

        $this->closeEdit();
        session()->flash('status', 'Barang berhasil diperbarui.');
    }

    /**
     * @param  int  $direction  1 untuk menambah, -1 untuk mengurangi
     */
    public function adjust(int $direction, StockService $service): void
    {
        $this->authorize('update', $this->stock);

        $this->validate([
            'adjustAmount' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $delta = ($direction < 0 ? -1 : 1) * (int) $this->adjustAmount;

        try {
            $this->stock = $service->adjustQuantity($this->stock, $delta);
            session()->flash('status', 'Quantity berhasil diperbarui.');
        } catch (InvalidArgumentException $e) {
            $this->addError('adjustAmount', $e->getMessage());
        }
    }

    public function uploadCover(StockService $service): void
    {
        $this->authorize('update', $this->stock);

        $this->validate([
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->stock = $service->storeCover($this->stock, $this->cover);
        $this->reset('cover');

        session()->flash('status', 'Cover berhasil diunggah.');
    }

    public function removeCover(StockService $service): void
    {
        $this->authorize('update', $this->stock);

        $this->stock = $service->removeCover($this->stock);

        session()->flash('status', 'Cover berhasil dihapus.');
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->stock);

        $this->showDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->showDelete = false;
    }

    public function delete(StockService $service): void
    {
        $this->authorize('delete', $this->stock);

        $service->delete($this->stock);
        session()->flash('status', 'Barang berhasil dihapus.');

        $this->redirectRoute('stocks.index');
    }

    public function render(): View
    {
        return view('livewire.stock.stock-detail', [
            'descriptionHtml' => Str::markdown($this->stock->description ?? '', [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }
}
