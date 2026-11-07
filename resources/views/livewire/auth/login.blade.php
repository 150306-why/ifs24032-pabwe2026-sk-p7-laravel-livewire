<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-xl font-semibold text-slate-900">Masuk ke akun Anda</h1>
    <p class="mt-1 text-sm text-slate-600">Kelola inventaris barang Anda dengan mudah.</p>

    <form wire:submit="submit" class="mt-6 space-y-4">
        <div>
            <label for="login-username" class="block text-sm font-medium">Username atau Email</label>
            <input id="login-username" type="text" wire:model="login" autocomplete="username" autofocus
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            @error('login') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="login-password" class="block text-sm font-medium">Password</label>
            <input id="login-password" type="password" wire:model="password" autocomplete="current-password"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300"> Ingat saya
        </label>
        <button id="login-submit" type="submit" wire:loading.attr="disabled"
                class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="submit">Masuk</span>
            <span wire:loading wire:target="submit">Memproses...</span>
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-600">
        Belum punya akun? <a href="{{ route('register') }}" class="font-medium text-blue-700 underline">Daftar</a>
    </p>
</div>
