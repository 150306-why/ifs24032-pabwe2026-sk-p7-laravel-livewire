# STOCK THINGS — PABWE 2026 Studi Kasus C

Laravel 13 · Livewire 4 · MySQL · PhpSpreadsheet · OpenAI · Clean Architecture + Feature-First

> **Status jujur:** seluruh kode ini ditulis tanpa bisa dijalankan (lingkungan penulis tidak punya PHP/Composer).
> Syntax sudah dicek kasar, tetapi **belum ada satu pun test yang benar-benar dijalankan**.
> Jalankan Langkah 4 di bawah, lalu kirim output error (jika ada) agar bisa diperbaiki. Target coverage 100% baru boleh diklaim setelah `composer coverage` Anda menunjukkannya.

## Langkah 1 — Siapkan proyek

**Opsi A (disarankan): proyek baru**

```powershell
cd D:\Praktikum
composer create-project laravel/laravel ifs24032-pabwe2026-sk-p7-laravel-livewire
cd ifs24032-pabwe2026-sk-p7-laravel-livewire
```

**Opsi B: salin dari Todolist.** Salin folder Todolist (tanpa `vendor`, `node_modules`, `.git`), lalu hapus sisa domain Todo
(`app/Features/Todo`, `database/migrations/*todos*`, `database/factories/Todo*`, `resources/views/livewire/todo*`, `tests/**/Todo*`)
agar tidak ada class duplikat. Pola Auth/Profile/Admin/layout dari Todolist Anda **ditimpa** oleh versi di paket ini.

## Langkah 2 — Tempel paket

Ekstrak `stock-things-pack.zip` **ke root proyek** (pilih *Replace/Overwrite* untuk file yang sudah ada), lalu:

```powershell
powershell -ExecutionPolicy Bypass -File .\install-stock-things.ps1
composer require livewire/livewire phpoffice/phpspreadsheet
composer require --dev phpunit/phpunit    # lewati jika sudah ada
```

Pastikan ekstensi PHP aktif: `gd`, `zip`, `mbstring`, `pdo_mysql`, `pdo_sqlite` (untuk test), dan **Xdebug atau PCOV** (untuk coverage).

## Langkah 3 — Database & jalankan

Buat database `pabwe_p7_stock` di MySQL, cek `DB_USERNAME`/`DB_PASSWORD` di `.env`, lalu:

```powershell
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build        # atau: npm run dev (terminal terpisah)
php artisan serve
```

Buka `http://127.0.0.1:8000`. Akun pengembangan: **admin / admin** dan **member / member**.
Tanpa `npm run build`, layout otomatis memakai Tailwind CDN (butuh internet).

OpenAI: isi `OPENAI_API_KEY=` **hanya di `.env`** (sudah di-ignore Git).

## Langkah 4 — Verifikasi (wajib)

```powershell
composer test
vendor\bin\phpunit --coverage-text
composer audit
```

Yang harus Anda lihat: semua test hijau, **tanpa Notice/Warning**, dan `Classes / Methods / Lines = 100.00%`.
Jika ada error, kirim pesan lengkapnya (file + baris) — jangan diperbaiki membabi buta.

Checklist manual (browser):

- [ ] `/` → login (guest); login admin → `/admin/users`; login member → `/stocks`
- [ ] Member: tambah/edit/hapus barang; search nama & SKU; filter status; pagination
- [ ] Tombol −/+ mengubah quantity; tidak bisa di bawah 0; status berubah otomatis
- [ ] Detail `/stocks/{id}`: upload cover, deskripsi Markdown tampil, adjust quantity, edit via Toast UI
- [ ] Export Excel hanya berisi stok sendiri; Import: baris valid masuk, baris invalid dilaporkan
- [ ] AI Generator: preview → pilih item → hanya yang dipilih tersimpan
- [ ] Profil: ubah info, password, foto
- [ ] Admin: lihat ringkasan, buka stok user — tanpa tombol edit/hapus
- [ ] Member membuka stok user lain (`/stocks/{id}` milik orang lain) → 403

## Peta file (Feature-First)

```
app/Features/
  Auth/     Actions/{Login,Register}UserAction · Livewire/{Login,Register}
  Profile/  Services/ProfileService · Livewire/Profile
  Stock/    Enums/StockStatus · Models/Stock · Contracts/StockRepositoryInterface
            Repositories/StockRepository · Policies/StockPolicy
            Services/{StockService,StockExcelService}
            Livewire/{StockList,StockDetail,Forms/StockForm}
  Ai/       Contracts/AiClientInterface · DTO/GeneratedStockItem · Services/OpenAiStockGeneratorService
  Admin/    Services/AdminUserService · Livewire/{UserList,AdminUserStockList}
app/Http/Middleware/{EnsureRole,EnsureStockOwnership}   alias: role, owns.stock
```

## Keputusan desain yang perlu Anda ketahui

1. **Status selalu dihitung otomatis** oleh `StockService::resolveStatus()`; field Status di modal bersifat read-only (pratinjau), dan kolom `status` pada Excel/AI diabaikan lalu dihitung ulang.
2. **Admin tidak punya akses `/stocks` maupun `/stocks/{id}`** (403). Admin hanya membaca lewat `/admin/users/{user}/stocks`, yang tidak memiliki aksi ubah/hapus.
3. **Header Excel** mengikuti master prompt: `name, sku, qty, unit, price, min_stock, status`. Modul menulis elipsis (`…`) setelahnya — cocokkan dengan `TodoExcelService` Anda bila dosen meminta kolom tambahan.
4. **AI**: `OpenAiStockGeneratorService` mengimplementasikan `AiClientInterface`; test memakai `createStub()` dan `Http::fake()`, tidak memanggil OpenAI sungguhan. Data dari browser divalidasi ulang sebelum disimpan.
5. **Rate limit** `1000-per-second` dan **FORCE_HTTPS** (`config('app.force_https')`) dipasang di `AppServiceProvider`.
6. Jenkinsfile memakai agent Linux (`sh`), gate coverage 100% (Classes/Methods/Lines), `composer audit --locked`, dan Trivy bila terpasang. Sesuaikan jika Jenkins Anda berjalan di Windows (`bat`).

## Titik yang paling mungkin perlu penyesuaian (karena belum diuji)

- API Livewire 4 (`assertFileDownloaded`, Form object `reset()`, `#[Locked]` pada model) — jika nama method berbeda di versi terpasang, sesuaikan di `tests/Feature/**`.
- `bootstrap/app.php` ditimpa penuh; jika Todolist Anda menambah middleware lain, gabungkan manual.
- Versi PHPUnit: `phpunit.xml` memakai `<source>` dan `failOnNotice` (PHPUnit 10.1+).
