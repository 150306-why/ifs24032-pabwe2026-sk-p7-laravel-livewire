# STOCK THINGS - skrip pasca-salin. Jalankan dari ROOT proyek Laravel (folder yang berisi file "artisan").
#   powershell -ExecutionPolicy Bypass -File .\install-stock-things.ps1
$ErrorActionPreference = 'Stop'

if (-not (Test-Path 'artisan')) { throw 'Jalankan skrip ini dari root proyek Laravel (folder yang berisi file artisan).' }

function Set-EnvValue([string]$File, [string]$Key, [string]$Value) {
    if (-not (Test-Path $File)) { return }
    $content = Get-Content $File -Raw
    $pattern = "(?m)^#?\s*$([regex]::Escape($Key))=.*$"
    if ($content -match $pattern) {
        $content = [regex]::Replace($content, $pattern, "$Key=$Value")
        Set-Content -Path $File -Value $content -NoNewline
    } else {
        Add-Content -Path $File -Value "`n$Key=$Value"
    }
}

Write-Host '1/5 Menambahkan force_https ke config/app.php ...'
$cfg = Get-Content 'config/app.php' -Raw
if ($cfg -notmatch 'force_https') {
    $pattern = "('url'\s*=>\s*env\('APP_URL',\s*'http://localhost'\),)"
    if ($cfg -match $pattern) {
        $cfg = [regex]::Replace($cfg, $pattern, "`$1`r`n`r`n    'force_https' => (bool) env('FORCE_HTTPS', false),", 1)
        Set-Content -Path 'config/app.php' -Value $cfg -NoNewline
    } else {
        Write-Warning "Baris 'url' tidak ditemukan. Tambahkan manual di config/app.php:  'force_https' => (bool) env('FORCE_HTTPS', false),"
    }
}

Write-Host '2/5 Mengatur .env dan .env.example ...'
foreach ($f in '.env', '.env.example') {
    Set-EnvValue $f 'APP_NAME'       '"STOCK THINGS"'
    Set-EnvValue $f 'APP_LOCALE'     'id'
    Set-EnvValue $f 'DB_CONNECTION'  'mysql'
    Set-EnvValue $f 'DB_HOST'        '127.0.0.1'
    Set-EnvValue $f 'DB_PORT'        '3306'
    Set-EnvValue $f 'DB_DATABASE'    'pabwe_p7_stock'
    Set-EnvValue $f 'DB_USERNAME'    'root'
    Set-EnvValue $f 'FORCE_HTTPS'    'false'
    Set-EnvValue $f 'OPENAI_MODEL'   'gpt-4o-mini'
}
# API key HANYA di .env (tidak di .env.example)
if ((Test-Path '.env') -and -not (Select-String -Path '.env' -Pattern '^OPENAI_API_KEY=' -Quiet)) { Add-Content '.env' "`nOPENAI_API_KEY=" }
if ((Test-Path '.env.example') -and -not (Select-String -Path '.env.example' -Pattern '^OPENAI_API_KEY=' -Quiet)) { Add-Content '.env.example' "`nOPENAI_API_KEY=" }

Write-Host '3/5 Menghapus test contoh bawaan Laravel ...'
Remove-Item 'tests/Feature/ExampleTest.php', 'tests/Unit/ExampleTest.php' -ErrorAction SilentlyContinue

Write-Host '4/5 Memperbarui .gitignore ...'
$ignore = @('.env', '.env.*', '!.env.example', 'coverage.txt', 'coverage.xml', 'coverage/', '.phpunit.result.cache', '.phpunit.cache/', 'stock-things.zip')
$existing = if (Test-Path '.gitignore') { Get-Content '.gitignore' } else { @() }
foreach ($line in $ignore) { if ($existing -notcontains $line) { Add-Content '.gitignore' $line } }

Write-Host '5/5 Selesai.'
Write-Host ''
Write-Host 'Langkah berikutnya (lihat README-STOCK-THINGS.md):'
Write-Host '  composer require livewire/livewire phpoffice/phpspreadsheet'
Write-Host '  php artisan migrate:fresh --seed'
Write-Host '  php artisan storage:link'
Write-Host '  composer test'
