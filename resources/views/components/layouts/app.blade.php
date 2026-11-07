<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="STOCK THINGS adalah aplikasi manajemen stok dan inventaris barang berbasis web: kelola barang, atur stok masuk dan keluar, import dan export Excel.">
    <title>{{ isset($title) ? $title.' — ' : '' }}STOCK THINGS</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    @livewireStyles
</head>
<body class="h-full font-sans text-slate-800 antialiased">
@auth
    @php($authUser = auth()->user())
    <div x-data="{ open: false }" class="min-h-full">
        {{-- Sidebar --}}
        <aside aria-label="Sidebar" class="fixed inset-y-0 left-0 z-30 w-64 transform border-r border-slate-200 bg-white transition-transform lg:translate-x-0"
               :class="open ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center gap-2 border-b border-slate-200 px-6">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">ST</span>
                <span class="text-lg font-bold tracking-tight text-slate-900">STOCK THINGS</span>
            </div>
            <nav aria-label="Navigasi utama" class="space-y-1 p-4 text-sm font-medium">
                @if ($authUser->isMember())
                    <a href="{{ route('stocks.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('stocks.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Inventaris
                    </a>
                @endif
                @if ($authUser->isAdmin())
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('admin.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Pengguna
                    </a>
                @endif
                <a href="{{ route('profile') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('profile') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' }}">
                    Profil
                </a>
            </nav>
        </aside>
        <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-20 bg-slate-900/40 lg:hidden"></div>

        <div class="lg:pl-64">
            {{-- Header --}}
            <header class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
                <button type="button" @click="open = !open" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="hidden lg:block"></div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('profile') }}" class="flex items-center gap-2">
                        @if ($authUser->profilePhotoUrl())
                            <img src="{{ $authUser->profilePhotoUrl() }}" alt="Foto profil" class="h-9 w-9 rounded-full object-cover">
                        @else
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">{{ $authUser->initials() }}</span>
                        @endif
                        <span class="hidden text-sm sm:block">
                            <span class="block font-medium leading-4">{{ $authUser->name }}</span>
                            <span class="text-xs uppercase text-slate-600">{{ $authUser->role }}</span>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" id="logout-submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="mx-auto max-w-7xl p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
@else
    <main class="flex min-h-full flex-col items-center justify-center px-4 py-12">
        <div class="mb-6 flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 font-bold text-white">ST</span>
            <span class="text-2xl font-bold tracking-tight text-slate-900">STOCK THINGS</span>
        </div>
        <div class="w-full max-w-md">{{ $slot }}</div>
    </main>
@endauth

<script>
    // Toast UI Editor dimuat HANYA saat editor Markdown dibuka (modal Tambah/Edit, halaman detail),
    // supaya halaman lain tidak menanggung ~260 KB CSS/JS yang tidak terpakai.
    window.loadToastEditor = function () {
        if (window.toastui && window.toastui.Editor) {
            return Promise.resolve(window.toastui.Editor);
        }
        if (window.__toastEditorPromise) {
            return window.__toastEditorPromise;
        }
        window.__toastEditorPromise = new Promise(function (resolve, reject) {
            var css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://uicdn.toast.com/editor/3.2.2/toastui-editor.min.css';
            document.head.appendChild(css);

            var js = document.createElement('script');
            js.src = 'https://uicdn.toast.com/editor/3.2.2/toastui-editor-all.min.js';
            js.onload = function () { resolve(window.toastui.Editor); };
            js.onerror = function () {
                window.__toastEditorPromise = null;
                reject(new Error('Editor Markdown gagal dimuat.'));
            };
            document.body.appendChild(js);
        });
        return window.__toastEditorPromise;
    };
</script>
@livewireScripts
</body>
</html>
