<div>
    <a href="{{ route('admin.users.index') }}" class="mb-4 inline-block text-sm text-blue-600 hover:underline">&larr; Kembali ke daftar pengguna</a>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Stok milik {{ $user->name }}</h1>
        <p class="text-sm text-slate-600">{{ $user->username }} · {{ $user->email }} — <span class="font-medium text-amber-700">mode hanya baca</span></p>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([['Total Barang', $stats['total_items']], ['Total Quantity', $stats['total_quantity']], ['Stok Menipis', $stats['low_stock']], ['Stok Habis', $stats['out_of_stock']]] as [$label, $value])
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-600">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input type="search" aria-label="Cari barang berdasarkan nama atau SKU" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU..."
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm sm:max-w-sm">
        <select aria-label="Filter status barang" wire:model.live="statusFilter" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr><th class="px-4 py-3">Barang</th><th class="px-4 py-3">Quantity</th><th class="px-4 py-3">Harga</th><th class="px-4 py-3">Min</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($stocks as $stock)
                    <tr wire:key="stock-{{ $stock->id }}">
                        <td class="px-4 py-3"><div class="font-medium">{{ $stock->name }}</div><div class="text-xs text-slate-600">{{ $stock->sku ?: '—' }}</div></td>
                        <td class="px-4 py-3">{{ $stock->quantity }} {{ $stock->unit }}</td>
                        <td class="px-4 py-3">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '—' }}</td>
                        <td class="px-4 py-3">{{ $stock->min_stock }}</td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-600">Pengguna ini belum memiliki barang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $stocks->links() }}</div>
</div>
