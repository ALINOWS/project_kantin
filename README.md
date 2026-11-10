# KantinMuh MVC Backend

Paket ini berisi 13 Model, 13 migration, 10 controller, dan routes API berdasarkan dokumen KantinMuh_Revisi.docx.

## Pemasangan
1. Salin folder `app/Models`, `app/Http/Controllers`, dan `database/migrations` ke project Laravel.
2. Gabungkan isi `routes/api.php` ke `routes/api.php` project. Jika project belum memuat API routes, aktifkan melalui konfigurasi Laravel versi yang digunakan.
3. Atur `.env` untuk MySQL dan buat database `kantinmuh`.
4. Jalankan `php artisan migrate`.
5. Jalankan `php artisan route:list --path=api`.

## Catatan penting
- Endpoint memakai `auth:sanctum`; pasang Laravel Sanctum dan siapkan autentikasi terlebih dahulu.
- Untuk Laravel 11/12, pastikan API routes sudah diaktifkan (misalnya `php artisan install:api` bila sesuai versi).
- Endpoint `saya-sudah-bayar` hanya mencatat klaim siswa. QRIS statis tidak dapat dideteksi otomatis oleh website. Penjual tetap harus mencocokkan notifikasi transaksi QRIS.
- Kode unik dibuat acak 1-999 pada contoh checkout ini. Untuk menjamin tidak bentrok dengan pesanan aktif milik penjual yang sama, tambahkan transaksi/locking dan pemeriksaan unik berdasarkan merchant sebelum produksi.
- Pembatasan akses per penjual, validasi role rinci, upload file QRIS/foto produk, pembatalan/expired otomatis, notifikasi, dan audit log otomatis perlu dilengkapi sebelum deployment.
- `orders` menyimpan snapshot nama dan harga produk di `order_items`, sehingga histori tetap terbaca walau produk berubah.
- Sesuaikan status role dan enum bisnis dengan kebijakan final sekolah.
