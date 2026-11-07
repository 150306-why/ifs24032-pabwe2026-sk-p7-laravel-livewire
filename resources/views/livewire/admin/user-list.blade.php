<div>
    <x-flash />
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Pengguna</h1>
        <p class="text-sm text-slate-600">Ringkasan stok seluruh pengguna (hanya baca).</p>
    </div>

    <div class="mb-4 flex items-center gap-3">
        <input type="search" aria-label="Cari pengguna" wire:model.live.debounce.300ms="search" placeholder="Cari nama, username, email..."
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm sm:max-w-sm">
        <span wire:loading wire:target="search" class="text-sm text-slate-600">Memuat...</span>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr><th class="px-4 py-3">Pengguna</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Jumlah Barang</th><th class="px-4 py-3">Total Quantity</th><th class="px-4 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3"><div class="font-medium">{{ $user->name }}</div><div class="text-xs text-slate-600">{{ $user->username }} · {{ $user->email }}</div></td>
                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium uppercase">{{ $user->role }}</span></td>
                        <td class="px-4 py-3">{{ $user->stocks_count }}</td>
                        <td class="px-4 py-3">{{ number_format((int) $user->stocks_sum_quantity, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.users.stocks', $user) }}" class="text-blue-600 hover:underline">Lihat stok</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-600">Tidak ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</div>
