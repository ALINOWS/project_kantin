# Panduan Belajar Membangun Website KantinMuh

Dokumen ini menjelaskan cara kerja aplikasi KantinMuh, alasan di balik bagian-bagian utamanya, serta urutan yang bisa diikuti untuk mempelajari dan mengembangkan proyek. Tujuannya bukan hanya menjalankan aplikasi, tetapi memahami bagaimana kebutuhan kantin diterjemahkan menjadi route, controller, model, database, dan tampilan Laravel.

> **Penting tentang pembayaran:** aplikasi menggunakan gambar QRIS statis. Aplikasi tidak menerima konfirmasi otomatis dari bank atau payment gateway. Klaim “sudah membayar” dari siswa hanya memberi tahu penjual; penjual tetap harus mencocokkan transaksi dan nominal secara manual.

## 1. Gambaran aplikasi

KantinMuh mempunyai tiga jenis pengguna:

| Peran | Tanggung jawab utama |
|---|---|
| Siswa | Melihat menu, mengisi keranjang, membuat pesanan, membayar melalui QRIS, mengirim klaim pembayaran, mengambil pesanan, dan memberi ulasan. |
| Penjual | Mengelola menu dan QRIS kantin, memverifikasi pembayaran, menyiapkan pesanan, lalu mencocokkan kode pengambilan. |
| Admin | Mengelola pengguna dan kategori, menyetujui penjual, membuat QR menu, serta melihat laporan dan audit aktivitas. |

Ada dua jenis QR dengan fungsi berbeda:

1. **QR menu** membuka halaman katalog kantin tertentu. QR ini dikelola melalui halaman admin dan menggunakan alamat `/q/{token}`.
2. **QRIS** adalah gambar pembayaran milik penjual. Siswa memindainya di aplikasi pembayaran. QRIS ini tidak mengonfirmasi pembayaran kembali ke KantinMuh.

## 2. Teknologi dan pola arsitektur

Proyek ini menggunakan Laravel, PHP, Blade, MySQL, dan Vite. JavaScript menggunakan paket `qrcode` untuk menggambar QR menu di browser.

Alur request web secara sederhana:

```text
Browser
  -> route di routes/web.php
  -> middleware (login dan pemeriksaan peran)
  -> controller (validasi, aturan aplikasi, dan query)
  -> model / database
  -> Blade view
  -> HTML kembali ke browser
```

### Peran komponen

- **Route** menentukan URL, HTTP method, nama route, controller, dan middleware.
- **Middleware** menyaring request sebelum controller dijalankan, misalnya memastikan pengguna login dan mempunyai peran yang tepat.
- **Controller** mengoordinasikan alur: validasi input, membaca atau mengubah data, dan memilih response.
- **Model** mewakili tabel dan hubungan antardata menggunakan Eloquent.
- **Migration** mendeskripsikan struktur database dalam bentuk kode yang bisa dilacak versinya.
- **Blade view** menyajikan HTML dengan data dari controller.
- **Service** menyimpan fungsi lintas-alur yang dipakai beberapa controller, seperti pencatatan audit.
- **Feature test** mengirim request ke aplikasi dan memeriksa hasil serta perubahan database.

## 3. Peta folder penting

Semua perintah Laravel di panduan ini dijalankan dari folder `kantin`.

| Lokasi | Isi |
|---|---|
| `routes/web.php` | Daftar route publik, siswa, penjual, dan admin. |
| `app/Http/Controllers/` | Controller katalog, keranjang, checkout, pesanan, autentikasi, admin, dan penjual. |
| `app/Http/Middleware/EnsureRole.php` | Pemeriksaan status akun dan peran. |
| `app/Models/` | Model pengguna, menu, kantin, pesanan, pembayaran, ulasan, dan lainnya. |
| `app/Services/AuditLogService.php` | Pencatatan tindakan penting ke audit log. |
| `app/Console/Commands/` | Perintah Artisan khusus, termasuk membuat admin dan mengakhiri pesanan kedaluwarsa. |
| `database/migrations/` | Perubahan struktur tabel. |
| `database/factories/` | Data contoh untuk kebutuhan pengujian. |
| `resources/views/` | Tampilan Blade untuk katalog, akun, keranjang, pesanan, admin, dan penjual. |
| `resources/js/app.js` | Pembuatan gambar QR menu dan tombol salin URL. |
| `resources/css/app.css` | Gaya tampilan aplikasi. |
| `tests/Feature/` | Pengujian alur katalog, akses peran, checkout, pembayaran, dan pengambilan. |

Beberapa berkas utama untuk dibaca saat belajar:

- [Daftar route](../routes/web.php)
- [Controller checkout](../app/Http/Controllers/CheckoutController.php)
- [Controller pesanan siswa](../app/Http/Controllers/OrderController.php)
- [Controller pesanan penjual](../app/Http/Controllers/Merchant/OrderController.php)
- [Middleware peran](../app/Http/Middleware/EnsureRole.php)
- [Pengujian alur pemesanan](../tests/Feature/OrderingFlowTest.php)

## 4. Memahami database dan hubungan model

Struktur data dirancang agar setiap tabel mempunyai satu tanggung jawab utama.

```text
users
  ├── merchant_profiles (profil kantin untuk akun penjual)
  ├── carts -> cart_items -> products
  ├── orders -> order_items
  │              ├── payments
  │              └── reviews
  ├── user_notifications
  └── audit_logs

merchant_profiles
  ├── products
  ├── orders
  └── qr_codes

categories
  └── products
```

| Tabel | Kegunaan |
|---|---|
| `users` | Identitas login, role, dan status akun. |
| `merchant_profiles` | Nama kantin, jam buka, status persetujuan, serta lokasi gambar QRIS. |
| `categories` | Kategori menu seperti makanan dan minuman. |
| `products` | Menu, harga, kategori, kantin pemilik, foto, dan status ketersediaan. |
| `carts`, `cart_items` | Keranjang aktif siswa dan jumlah setiap produk. |
| `orders` | Nomor pesanan, status, subtotal, total, kantin, tenggat, dan kode pengambilan. |
| `order_items` | Salinan nama dan harga menu ketika pesanan dibuat. |
| `payments` | Status serta informasi pembayaran yang terkait dengan satu pesanan. |
| `reviews` | Nilai dan komentar siswa untuk pesanan yang telah selesai. |
| `qr_codes` | Token dan URL QR menu kantin. |
| `user_notifications` | Pemberitahuan untuk siswa atau penjual. |
| `audit_logs` | Catatan tindakan penting beserta pelaku dan waktu. |

### Mengapa `order_items` menyimpan salinan nama dan harga?

Harga menu dapat berubah setelah pesanan dilakukan. Karena itu, detail pesanan menyimpan `product_name` dan `unit_price` sebagai snapshot. Riwayat transaksi tetap menunjukkan harga saat transaksi dibuat, bukan harga menu terbaru.

Produk juga dihubungkan dengan `merchant_id`. Dengan demikian, penjual hanya dapat melihat dan mengubah produk serta pesanan milik kantinnya sendiri.

### Migration dan database yang sudah berisi data

Migration adalah versi skema database yang disimpan bersama source code. Migration proyek ini menambahkan tabel serta kolom untuk fitur kantin, dan beberapa migration memeriksa keberadaan tabel sebelum membuatnya.

Sebelum menerapkan migration pada database yang sudah berisi data:

1. Buat backup database.
2. Periksa konfigurasi koneksi di `.env`.
3. Tinjau migration dan cocokkan dengan skema database aktual.
4. Jalankan `php artisan migrate` hanya setelah yakin target koneksinya benar.

**Jangan gunakan `php artisan migrate:fresh` atau `php artisan migrate:refresh` pada database yang berisi data penting.** Perintah tersebut dapat menghapus tabel atau data. Menjalankan migration belum dilakukan sebagai bagian dari penyusunan panduan ini.

## 5. Autentikasi dan pembatasan akses

Route dibagi berdasarkan siapa yang boleh menggunakannya:

- Katalog dan detail menu tersedia untuk pengunjung.
- Keranjang, checkout, pesanan, dan ulasan hanya untuk siswa yang login.
- Dashboard, produk, pesanan, QRIS, dan laporan penjual berada di grup `merchant`.
- Pengelolaan pengguna, kategori, QR menu, audit, dan laporan admin berada di grup `admin`.

Middleware `role` memeriksa bahwa:

1. Pengguna sudah login.
2. Status akun pengguna aktif.
3. Role cocok dengan route yang dibuka.
4. Jika pengguna adalah penjual, profil kantinnya juga berstatus aktif.

Pendaftaran penjual membuat profil yang menunggu persetujuan. Admin harus mengaktifkan profil tersebut sebelum penjual dapat memakai fitur penjual. Pemeriksaan ini ada di sisi server; menyembunyikan tombol di tampilan saja tidak cukup untuk mengamankan route.

## 6. Alur pemesanan siswa

### Langkah 1: memasukkan menu ke keranjang

Controller keranjang memvalidasi produk dan jumlah, lalu memastikan menu, kategori, serta kantin masih aktif. Satu keranjang hanya boleh berisi produk dari **satu kantin**. Aturan ini menghindari satu pembayaran harus dibagi ke beberapa penjual.

### Langkah 2: checkout

Controller checkout menjalankan transaksi database:

1. Mengunci dan membaca keranjang.
2. Memastikan keranjang tidak kosong dan hanya mempunyai satu kantin.
3. Memastikan semua menu masih tersedia dan kantin mempunyai gambar QRIS.
4. Menghitung subtotal.
5. Menghasilkan kode unik pembayaran antara 1–999.
6. Membuat pesanan dan salinan detail menu di `order_items`.
7. Membuat catatan `payments`.
8. Mengosongkan serta menandai keranjang sudah dikonversi.
9. Mencatat tindakan checkout ke audit log.

Nominal tagihan adalah subtotal ditambah kode unik. Contoh:

```text
Subtotal menu        Rp20.000
Kode unik            Rp     37
Nominal yang dibayar Rp20.037
```

Perhitungan uang dilakukan dalam satuan integer sen di proses perhitungan untuk mengurangi kesalahan pecahan desimal, lalu disimpan sebagai nilai desimal dua angka di database.

Kode unik hanya dipilih dari 1–999 dan tidak digunakan ulang untuk pembayaran pending kantin yang sama. Karena ruang kodenya terbatas, sistem dapat meminta siswa mencoba lagi ketika banyak pembayaran masih menunggu. Pesanan yang belum dibayar mempunyai waktu 15 menit.

### Langkah 3: siswa membayar dan mengirim klaim

Siswa memindai QRIS kantin dan membayar nominal yang persis ditampilkan. Setelah itu siswa menekan tombol klaim pembayaran. Status menjadi menunggu pemeriksaan penjual, tetapi **belum** dianggap lunas.

### Langkah 4: penjual memverifikasi pembayaran

Penjual membuka detail pesanan dan mencocokkan transaksi di aplikasi/bank dengan nominal pesanan. Penjual memasukkan nominal yang benar-benar diterima:

- Jika sama, status pembayaran menjadi `paid`, pesanan menjadi `processing`, dan dibuat kode pengambilan.
- Jika berbeda, sistem menolak perubahan dan pesanan tetap menunggu pemeriksaan.
- Penjual juga dapat menolak klaim dengan alasan.

### Langkah 5: menyiapkan dan menyerahkan pesanan

Setelah pesanan siap, penjual mengubah status menjadi siap diambil. Siswa menunjukkan kode pengambilan kepada penjual. Kode hanya diterima ketika cocok dan status pesanan benar; setelah pesanan selesai, kode dihapus sehingga tidak dapat digunakan kembali.

### Langkah 6: ulasan

Siswa dapat memberi satu ulasan setelah pesanan selesai. Server memeriksa pemilik pesanan, status selesai, status pembayaran, dan apakah ulasan sudah pernah dibuat.

## 7. Arti status pesanan dan pembayaran

Status pesanan menggambarkan proses pemenuhan barang. Status pembayaran menggambarkan pencocokan uang. Keduanya adalah hal berbeda.

| Status pesanan | Makna |
|---|---|
| `pending_payment` | Pesanan dibuat; siswa harus membayar sebelum tenggat. |
| `awaiting_merchant` | Siswa mengklaim sudah membayar; menunggu verifikasi penjual. |
| `processing` | Pembayaran sudah dicocokkan; pesanan sedang disiapkan. |
| `ready_for_pickup` | Pesanan siap diambil; kode pengambilan berlaku. |
| `completed` | Pesanan sudah diserahkan dan kode tidak berlaku lagi. |
| `cancelled` | Pesanan dibatalkan, misalnya klaim pembayaran ditolak. |
| `expired` | Waktu pembayaran habis sebelum pembayaran diklaim. |

Status pembayaran yang digunakan antara lain `pending`, `paid`, `failed`, dan `expired`. Contoh: pesanan `processing` harus memiliki pembayaran `paid`.

## 8. QR menu, upload, notifikasi, dan audit

### QR menu

Admin membuat QR menu untuk kantin aktif. QR menyimpan token acak dan URL scan. Ketika dipindai, route `/q/{token}` memeriksa bahwa QR masih aktif dan kantin aktif, lalu mengarahkan pengunjung ke katalog kantin tersebut. JavaScript di `resources/js/app.js` menggambar QR dari URL menggunakan paket `qrcode`.

### Upload gambar

Foto produk dan QRIS disimpan pada disk `public`, bukan sebagai isi gambar langsung di tabel. Database menyimpan path file. Perintah `php artisan storage:link` menghubungkan penyimpanan publik ke folder yang dapat dilayani web server.

### Notifikasi dan audit

Notifikasi membantu memberi tahu siswa atau penjual mengenai perubahan pesanan. Audit log adalah catatan untuk admin mengenai tindakan penting, seperti perubahan produk, klaim pembayaran, verifikasi, dan penyelesaian pesanan. Notifikasi ditujukan untuk komunikasi aplikasi; audit log ditujukan untuk jejak aktivitas.

## 9. Menyiapkan dan menjalankan aplikasi

Pastikan PHP, Composer, Node.js/npm, dan MySQL tersedia. Buka PowerShell pada folder proyek, lalu:

```powershell
Set-Location .\kantin
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Atur `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`. Jangan membagikan file `.env` karena berisi konfigurasi rahasia.

Pasang frontend dan bangun aset:

```powershell
npm ci
npm run build
```

Setelah backup dan pemeriksaan database, jalankan migration:

```powershell
php artisan migrate
```

Buat tautan storage, buat admin pertama secara interaktif, lalu mulai server:

```powershell
php artisan storage:link
php artisan kantin:create-admin-user admin@contoh.sch.id
php artisan serve
```

Perintah admin akan meminta nama dan kata sandi secara interaktif. Jangan memasukkan kredensial admin ke source code atau seeder.

Agar pesanan kedaluwarsa otomatis, scheduler harus dijalankan. Untuk pengembangan lokal, buka terminal lain:

```powershell
Set-Location .\kantin
php artisan schedule:work
```

Untuk server Windows, konfigurasikan Task Scheduler untuk menjalankan `php artisan schedule:run` setiap menit. Perintah terjadwal mengubah pesanan yang belum dibayar dan sudah melewati tenggat menjadi kedaluwarsa.

## 10. Cara menguji dan membaca kode

Jalankan pengujian dari folder `kantin`:

```powershell
php artisan test
```

Pengujian memakai database test yang disiapkan Laravel. Jangan arahkan test ke database produksi. Tes feature bermanfaat karena memeriksa beberapa lapisan sekaligus: route, middleware, controller, database, dan response.

Contoh cara mengikuti sebuah fitur:

1. Cari URL dan nama route di `routes/web.php`.
2. Buka controller yang disebut route.
3. Periksa model dan relasi yang dipakai controller.
4. Periksa migration untuk melihat bentuk kolom dan constraint.
5. Buka Blade view untuk memahami formulir dan tampilan hasil.
6. Cari feature test terkait untuk melihat contoh input yang sah dan perilaku yang diharapkan.

Saat terjadi error:

- `403`: pengguna tidak mempunyai role/status yang diperlukan.
- `404`: data tidak ditemukan atau memang disembunyikan dari pengguna yang tidak berhak.
- `419`: token CSRF atau sesi form tidak valid/kedaluwarsa.
- Error koneksi database: periksa `.env`, layanan MySQL, dan nama database.
- Gambar tidak tampil: periksa file di `storage/app/public` dan tautan `public/storage`.
- Pesanan tidak otomatis kedaluwarsa: pastikan scheduler berjalan.

## 11. Urutan belajar yang disarankan

1. **Laravel dasar:** route, controller, Blade, request, dan response.
2. **Eloquent:** model, relasi `belongsTo`, `hasMany`, `hasOne`, dan query builder.
3. **Form dan validasi:** pelajari `$request->validate()` dan pesan error di Blade.
4. **Autentikasi dan middleware:** telusuri login dan grup route siswa/penjual/admin.
5. **CRUD menu:** ikuti proses membuat dan memperbarui produk sampai database.
6. **Keranjang:** pahami relasi keranjang-item-produk dan aturan satu kantin.
7. **Transaksi checkout:** pelajari `DB::transaction`, row lock, snapshot harga, dan kode unik.
8. **State machine pesanan:** ikuti perubahan status dari pembayaran sampai selesai.
9. **Testing:** ubah satu aturan, tambahkan test yang membuktikan perilaku, lalu jalankan `php artisan test`.
10. **Deployment:** pelajari HTTPS, konfigurasi production, backup, monitoring scheduler, dan kebijakan penyimpanan file.

### Latihan pengembangan

- Tambahkan filter kategori pada katalog, lalu buat test untuk hasil filter.
- Tambahkan batas maksimum jumlah barang di keranjang dengan validasi dan pesan yang jelas.
- Tambahkan pencarian pesanan untuk penjual, dengan query tetap dibatasi pada `merchant_id` miliknya.
- Tambahkan test bahwa siswa tidak dapat melihat pesanan siswa lain.
- Tambahkan export laporan setelah menentukan format dan batas data yang aman.

Untuk setiap perubahan, pikirkan tiga hal: **siapa yang boleh melakukannya, perubahan status apa yang sah, dan bagaimana perilaku itu diuji?**

## 12. Batasan dan langkah sebelum produksi

- Pembayaran belum diverifikasi otomatis; penjual bertanggung jawab mencocokkan transaksi QRIS.
- QRIS harus diunggah dan dipastikan benar oleh penjual.
- Migration belum dijalankan pada database lama saat panduan ini dibuat. Cocokkan skema aktual dan buat backup terlebih dahulu.
- Konfigurasi produksi perlu memakai HTTPS, `APP_DEBUG=false`, kredensial rahasia yang aman, backup database terjadwal, serta web server yang hanya mengekspos folder `public`.
- Pastikan scheduler berjalan terus atau dipanggil setiap menit oleh penjadwal OS.
- Uji alur dengan akun siswa dan penjual di lingkungan staging sebelum digunakan untuk transaksi nyata.

## 13. Ringkasan konsep yang perlu diingat

- Route menentukan pintu masuk; middleware menentukan siapa yang boleh lewat.
- Controller memproses request; model mewakili data; view menampilkan hasil.
- Migration adalah perubahan skema yang perlu ditinjau sebelum menyentuh database berisi data.
- Data detail pesanan menyimpan snapshot agar riwayat tidak berubah saat harga menu diperbarui.
- Klaim pembayaran bukan verifikasi pembayaran.
- Kode pengambilan hanya berlaku setelah pembayaran cocok dan hanya dapat dipakai sekali.
- Feature test mendokumentasikan sekaligus menjaga perilaku aplikasi.
