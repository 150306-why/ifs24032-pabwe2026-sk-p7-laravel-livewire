<div class="mx-auto max-w-3xl space-y-6">
    <x-flash />
    <h1 class="text-2xl font-bold text-slate-900">Profil</h1>

    @php($inputClass = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200')

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">Foto Profil</h2>
        <div class="flex items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="Foto profil" class="h-20 w-20 rounded-full object-cover">
            @else
                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-blue-100 text-2xl font-semibold text-blue-700">{{ $user->initials() }}</span>
            @endif
            <form wire:submit="updatePhoto" class="space-y-2">
                <input type="file" wire:model="photo" accept="image/png,image/jpeg,image/webp" class="block text-sm">
                <div wire:loading wire:target="photo" class="text-sm text-slate-500">Mengunggah...</div>
                @error('photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updatePhoto,photo" class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Simpan foto</button>
                    @if ($user->profile_photo_path)
                        <button type="button" wire:click="removePhoto" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-100">Hapus foto</button>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">Informasi Profil</h2>
        <form wire:submit="updateProfile" class="space-y-4">
            <div><label class="block text-sm font-medium">Nama</label><input type="text" wire:model="name" class="{{ $inputClass }}">@error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium">Username</label><input type="text" wire:model="username" class="{{ $inputClass }}">@error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium">Email</label><input type="email" wire:model="email" class="{{ $inputClass }}">@error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Simpan</button>
        </form>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">Ubah Password</h2>
        <form wire:submit="updatePassword" class="space-y-4">
            <div><label class="block text-sm font-medium">Password saat ini</label><input type="password" wire:model="current_password" class="{{ $inputClass }}">@error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium">Password baru</label><input type="password" wire:model="password" class="{{ $inputClass }}">@error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium">Konfirmasi password baru</label><input type="password" wire:model="password_confirmation" class="{{ $inputClass }}"></div>
            <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Ubah password</button>
        </form>
    </section>
</div>
