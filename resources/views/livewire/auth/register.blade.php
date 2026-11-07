<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-xl font-semibold text-slate-900">Buat akun baru</h1>
    <p class="mt-1 text-sm text-slate-600">Daftar sebagai Member untuk mulai mengelola stok.</p>

    <form wire:submit="submit" class="mt-6 space-y-4">
        @foreach ([['name','Nama lengkap','text','name'],['username','Username','text','username'],['email','Email','email','email'],['password','Password','password','new-password'],['password_confirmation','Konfirmasi password','password','new-password']] as [$field,$label,$type,$auto])
            <div>
                <label for="register-{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                <input id="register-{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" autocomplete="{{ $auto }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <button id="register-submit" type="submit" wire:loading.attr="disabled"
                class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="submit">Daftar</span>
            <span wire:loading wire:target="submit">Memproses...</span>
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-600">
        Sudah punya akun? <a href="{{ route('login') }}" class="font-medium text-blue-700 underline">Masuk</a>
    </p>
</div>
