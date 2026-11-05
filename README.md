# ifs24051-pabwe2026-sk-1 p7-laravel-livewire — Stock Barang
Implementasi studi kasus P7 sesuai modul: Laravel 13, Livewire 4, MySQL, PhpSpreadsheet, feature-first.

## Prasyarat
PHP >= 8.3 + ekstensi `gd`, `zip`, `mbstring`, `pdo_mysql`, `pdo_sqlite`, Composer, Node/npm, MySQL.
## Instalasi lokal (PowerShell)
```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
# buat database pabwe_p7_stock di MySQL lalu sesuaikan DB_* di .env
npm install
npm run build
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve
```
Buka http://127.0.0.1:8000
- Admin: username `admin`, password `admin`
- Member: username `member`, password `member`
> Ganti password demo sebelum digunakan. Akun seed hanya untuk development.

## Struktur
`app/Features/{Admin,Ai,Auth,Profile,Stock}`; Stock terdiri atas Models, Enums, Contracts, Repositories, Services, Policies, Livewire.
## Catatan
- `.env` tidak disertakan.
- `vendor/` dan `node_modules/` sengaja tidak disertakan; install melalui Composer/npm.
- AI membutuhkan `OPENAI_API_KEY`; Excel membutuhkan ekstensi PHP `gd`, `zip`, `mbstring`.
- Jalankan `composer test`. Target coverage 100% modul; test yang disertakan adalah baseline dan perlu diperluas sebelum gate Jenkins 100% terpenuhi.
- Pastikan konfigurasi Jenkins credentials (URL_REDEPLOY, DEPLOY_TOKEN, WEBSITE_ID, URL_PROGRESS) tersedia di Jenkins environment.
