<div>
    <x-flash />

    <a href="{{ route('stocks.index') }}" class="mb-4 inline-block text-sm text-blue-600 hover:underline">&larr; Kembali ke inventaris</a>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ $stock->name }}</h1>
                        <p class="text-sm text-slate-600">SKU: {{ $stock->sku ?: '—' }}</p>
                    </div>
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div><dt class="text-slate-600">Quantity</dt><dd class="text-xl font-bold">{{ $stock->quantity }}</dd></div>
                    <div><dt class="text-slate-600">Unit</dt><dd class="text-xl font-bold">{{ $stock->unit }}</dd></div>
                    <div><dt class="text-slate-600">Harga</dt><dd class="text-xl font-bold">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '—' }}</dd></div>
                    <div><dt class="text-slate-600">Minimum Stock</dt><dd class="text-xl font-bold">{{ $stock->min_stock }}</dd></div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-2">
                    <button type="button" wire:click="openEdit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button>
                    <button type="button" wire:click="confirmDelete" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Hapus</button>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold">Deskripsi</h2>
                @if ($stock->description)
                    <div class="space-y-3 text-sm leading-6 [&_h1]:text-xl [&_h1]:font-bold [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:font-semibold [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_a]:text-blue-600 [&_a]:underline [&_code]:rounded [&_code]:bg-slate-100 [&_code]:px-1 [&_blockquote]:border-l-4 [&_blockquote]:pl-3 [&_blockquote]:text-slate-600">{!! $descriptionHtml !!}</div>
                @else
                    <p class="text-sm text-slate-600">Belum ada deskripsi.</p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            {{-- Cover --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold">Cover</h2>
                @if ($stock->coverUrl())
                    <img src="{{ $stock->coverUrl() }}" alt="Cover {{ $stock->name }}" class="mb-3 w-full rounded-lg object-cover">
                    <button type="button" wire:click="removeCover" class="mb-3 text-sm text-red-600 hover:underline">Hapus cover</button>
                @else
                    <div class="mb-3 flex h-40 items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-700">Belum ada cover</div>
                @endif
                <form wire:submit="uploadCover" class="space-y-2">
                    <input id="cover-input" aria-label="Pilih file cover" type="file" wire:model="cover" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm">
                    <div wire:loading wire:target="cover" class="text-sm text-slate-600">Mengunggah...</div>
                    @error('cover') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" wire:loading.attr="disabled" wire:target="uploadCover,cover" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium hover:bg-slate-100 disabled:opacity-60">Unggah cover</button>
                </form>
            </div>

            {{-- Adjust quantity --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold">Adjust Quantity</h2>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="adjust(-1)" class="rounded-lg border border-slate-300 px-3 py-2 font-bold hover:bg-slate-100">−</button>
                    <input id="adjust-amount" aria-label="Jumlah penyesuaian quantity" type="number" min="1" wire:model="adjustAmount" class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-center text-sm">
                    <button type="button" wire:click="adjust(1)" class="rounded-lg border border-slate-300 px-3 py-2 font-bold hover:bg-slate-100">+</button>
                    <span wire:loading wire:target="adjust" class="text-sm text-slate-600">...</span>
                </div>
                @error('adjustAmount') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    @if ($showEdit)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="my-8 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">Edit Barang</h2>
                <form wire:submit="save">
                    @include('livewire.stock.partials.form-fields')
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" wire:click="closeEdit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Simpan</span><span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDelete)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Hapus barang?</h2>
                <p class="mt-2 text-sm text-slate-600">“{{ $stock->name }}” akan dihapus permanen.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Batal</button>
                    <button type="button" wire:click="delete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Ya, hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
