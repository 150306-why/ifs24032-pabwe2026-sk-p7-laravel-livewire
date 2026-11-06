@if (session('status') || session('error'))
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
         class="mb-4 flex items-start justify-between rounded-lg border px-4 py-3 text-sm {{ session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' }}"
         role="alert">
        <span>{{ session('error') ?: session('status') }}</span>
        <button type="button" @click="show = false" class="ml-4 font-bold" aria-label="Tutup">&times;</button>
    </div>
@endif
