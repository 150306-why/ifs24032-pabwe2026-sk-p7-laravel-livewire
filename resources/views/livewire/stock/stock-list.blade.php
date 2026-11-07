<div>
    <x-flash />

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Inventaris</h1>
            <p class="text-sm text-slate-600">Kelola barang, stok masuk dan keluar.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium hover:bg-slate-100">
                <span wire:loading.remove wire:target="export">Export Excel</span><span wire:loading wire:target="export">Menyiapkan...</span>
            </button>
            <button type="button" wire:click="openImport" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium hover:bg-slate-100">Import Excel</button>
            <button type="button" wire:click="openAi" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">AI Generator</button>
            <button type="button" id="stock-create" wire:click="openCreate" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Tambah Barang</button>
        </div>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([['Total Barang', $stats['total_items'], 'text-slate-900'], ['Total Quantity', $stats['total_quantity'], 'text-blue-700'], ['Stok Menipis', $stats['low_stock'], 'text-yellow-700'], ['Stok Habis', $stats['out_of_stock'], 'text-red-700']] as [$label, $value, $color])
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-600">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- Search & filter --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input type="search" aria-label="Cari barang berdasarkan nama atau SKU" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU..."
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 sm:max-w-sm">
        <select aria-label="Filter status barang" wire:model.live="statusFilter" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
        <span wire:loading wire:target="search,statusFilter" class="self-center text-sm text-slate-600">Memuat...</span>
    </div>

    {{-- Tabel --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">Barang</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Harga</th>
                    <th class="px-4 py-3">Min</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($stocks as $stock)
                    <tr wire:key="stock-{{ $stock->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('stocks.show', $stock) }}" class="font-medium text-slate-900 hover:text-blue-700">{{ $stock->name }}</a>
                            <div class="text-xs text-slate-600">{{ $stock->sku ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="adjust({{ $stock->id }}, -1)" class="h-7 w-7 rounded border border-slate-300 font-bold hover:bg-slate-100" aria-label="Kurangi">−</button>
                                <span class="min-w-[3rem] text-center font-semibold">{{ $stock->quantity }} <span class="text-xs font-normal text-slate-600">{{ $stock->unit }}</span></span>
                                <button type="button" wire:click="adjust({{ $stock->id }}, 1)" class="h-7 w-7 rounded border border-slate-300 font-bold hover:bg-slate-100" aria-label="Tambah">+</button>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '—' }}</td>
                        <td class="px-4 py-3">{{ $stock->min_stock }}</td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('stocks.show', $stock) }}" class="mr-2 text-blue-600 hover:underline">Detail</a>
                            <button type="button" wire:click="openEdit({{ $stock->id }})" class="mr-2 text-slate-700 hover:underline">Edit</button>
                            <button type="button" wire:click="confirmDelete({{ $stock->id }})" class="text-red-600 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-slate-600">Belum ada barang. Klik “Tambah Barang” untuk memulai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $stocks->links() }}</div>

    {{-- Modal Create/Edit --}}
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit Barang' : 'Tambah Barang' }}</h2>
                <form wire:submit="save">
                    @include('livewire.stock.partials.form-fields')
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" wire:click="closeForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Simpan</span><span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Hapus --}}
    @if ($deletingId)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Hapus barang?</h2>
                <p class="mt-2 text-sm text-slate-600">“{{ $deletingName }}” akan dihapus permanen beserta cover-nya.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Batal</button>
                    <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">Ya, hapus</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Import --}}
    @if ($showImport)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Import dari Excel</h2>
                <p class="mt-1 text-sm text-slate-600">Header wajib: <code>name, sku, qty, unit, price, min_stock, status</code>. Kolom status dihitung ulang otomatis.</p>
                <form wire:submit="import" class="mt-4 space-y-3">
                    <input type="file" aria-label="Pilih file Excel" wire:model="importFile" accept=".xlsx,.xls" class="block w-full text-sm">
                    <div wire:loading wire:target="importFile" class="text-sm text-slate-600">Mengunggah file...</div>
                    @error('importFile') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    @if ($importResult)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                            <p class="font-medium text-green-700">{{ $importResult['imported'] }} baris berhasil diimpor.</p>
                            <p class="font-medium {{ $importResult['failed'] ? 'text-red-700' : 'text-slate-600' }}">{{ $importResult['failed'] }} baris gagal.</p>
                            @if ($importResult['errors'])
                                <ul class="mt-2 max-h-40 list-disc overflow-y-auto pl-5 text-red-700">
                                    @foreach ($importResult['errors'] as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeImport" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Tutup</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="import,importFile" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="import">Import</span><span wire:loading wire:target="import">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal AI Generator --}}
    @if ($showAi)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-3xl rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold">AI Stock Generator</h2>
                <p class="mt-1 text-sm text-slate-600">Jelaskan barang yang ingin dibuat. Hasil hanya disimpan setelah Anda memilihnya.</p>

                <form wire:submit="generateAi" class="mt-4 space-y-3">
                    <textarea aria-label="Prompt AI" wire:model="aiPrompt" rows="3" placeholder="Contoh: peralatan tulis kantor untuk toko buku kecil" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    @error('aiPrompt') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <div class="flex items-center gap-3">
                        <label for="ai-count" class="text-sm">Jumlah item</label>
                        <input id="ai-count" type="number" min="1" max="10" wire:model="aiCount" class="w-20 rounded-lg border border-slate-300 px-2 py-1 text-sm">
                        <button type="submit" wire:loading.attr="disabled" wire:target="generateAi" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="generateAi">Generate</span><span wire:loading wire:target="generateAi">AI sedang bekerja...</span>
                        </button>
                    </div>
                    @error('aiCount') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </form>

                @if ($aiItems)
                    <div class="mt-6">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="font-medium">Preview ({{ count($aiItems) }} item)</h3>
                            <button type="button" wire:click="selectAllAi" class="text-sm text-blue-600 hover:underline">Pilih semua</button>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                                    <tr><th class="px-3 py-2"><span class="sr-only">Pilih</span></th><th class="px-3 py-2">Nama</th><th class="px-3 py-2">SKU</th><th class="px-3 py-2">Qty</th><th class="px-3 py-2">Harga</th><th class="px-3 py-2">Status</th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($aiItems as $i => $item)
                                        <tr wire:key="ai-{{ $i }}">
                                            <td class="px-3 py-2"><input type="checkbox" aria-label="Pilih {{ $item['name'] }}" wire:model="aiSelected" value="{{ $i }}"></td>
                                            <td class="px-3 py-2"><div class="font-medium">{{ $item['name'] }}</div><div class="text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($item['description'] ?? '', 80) }}</div></td>
                                            <td class="px-3 py-2">{{ $item['sku'] ?? '—' }}</td>
                                            <td class="px-3 py-2">{{ $item['quantity'] }} {{ $item['unit'] }}</td>
                                            <td class="px-3 py-2">{{ $item['price'] !== null ? 'Rp '.number_format((float) $item['price'], 0, ',', '.') : '—' }}</td>
                                            <td class="px-3 py-2">@php($st = \App\Features\Stock\Enums\StockStatus::from($item['status']))<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $st->badgeClass() }}">{{ $st->label() }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('aiSelected') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeAi" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Tutup</button>
                    @if ($aiItems)
                        <button type="button" wire:click="saveAi" wire:loading.attr="disabled" wire:target="saveAi" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 disabled:opacity-60">Simpan item terpilih</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
