# Stock Barang — Laravel 13 + Livewire 4 (Feature-First)

Studi kasus Praktikum PABWE 2026 (P7): aplikasi inventaris stok barang.
Mengikuti pola latihan Todolist: Feature-First, Livewire, Auth, Profil, Admin read-only,
Excel (PhpSpreadsheet), AI (OpenAI), PHPUnit 100% coverage, Jenkins.

## Menjalankan

```bash
composer install
cp .env.example .env          # lalu isi DB_* dan OPENAI_API_KEY
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pabwe_p7_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan storage:link
php artisan migrate:fresh --seed
npm install --ignore-scripts
npm run build
php artisan serve
```

Akun seeder: `admin / admin` dan `member / member` (+ 5 contoh stok untuk member).

## Pengujian

```bash
composer test               # tanpa coverage
composer test:coverage      # coverage app/ wajib 100% (butuh pcov/xdebug)
composer audit
```

## Catatan desain

- **Status stok** dihitung otomatis dari `quantity` vs `min_stock`:
  `0 → out_of_stock`, `≤ min_stock → low_stock`, selainnya `available`.
- Admin hanya membaca (`/admin/users`, `/admin/users/{user}/stocks`); tidak ada aksi ubah/hapus.
- Middleware: `role:member|admin`, `owns.stock`; Policy: `StockPolicy`.
- Rate limit `1000-per-second`, `FORCE_HTTPS` lewat `.env`.
- Form login memakai id `login-username`, `login-password`, `login-submit`.
- Editor Markdown Toast UI dimuat dari CDN (butuh internet saat halaman detail dibuka).

## CI/CD (Jenkins)

`Jenkinsfile` memakai image `delcom-ci-php:8.5` (harus punya ext-gd, zip, pdo_sqlite, bcmath, exif + pcov/xdebug),
`node:24-alpine`, `aquasec/trivy`, dan `curlimages/curl`. Tahapan: Checkout → Composer install + `composer audit`
→ Build Vite → PHPUnit + gate coverage 100% (app/) → Trivy → Package `latest-app.zip` → Publish → Deploy.

Variabel yang harus tersedia di Jenkins (Environment/Credentials job): `URL_REDEPLOY`, `URL_PROGRESS`,
`DEPLOY_TOKEN`, `WEBSITE_ID`.
