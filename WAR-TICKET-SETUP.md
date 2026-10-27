# Panduan Event War Tiket

## Yang tersedia

- Admin dapat membuat, melihat, mengubah, menerbitkan, dan menghapus event war tiket.
- Setiap event memiliki nama, deskripsi, harga, stok, waktu mulai dan tutup penjualan. Jadwal memakai zona waktu aplikasi (default `Asia/Jakarta`).
- Pengguna melihat katalog event yang diterbitkan. Event terjadwal menampilkan hitung mundur.
- Tombol event membuka pop-up detail. Sebelum jadwal mulai, pop-up hanya menampilkan hitung mundur; CAPTCHA baru dimuat setelah server mengonfirmasi penjualan dibuka.
- Katalog dan pop-up memperbarui stok/status secara berkala. Setelah waktu penjualan berakhir, event menampilkan status berakhir; bila stok habis, event menampilkan tiket habis dan pemesanan dinonaktifkan.
- Event yang berakhir atau tiketnya habis tetap bisa dibuka untuk melihat ringkasan jumlah tiket terdaftar. Ringkasan hanya memperlihatkan agregat: jumlah tiket yang menunggu pembayaran dan yang lunas, tanpa membagikan nama, email, atau nomor telepon. Pesanan pending yang reservasinya sudah kedaluwarsa tidak dihitung.
- Saat event berlangsung, pengguna memilih jumlah tiket pada pop-up, memeriksa CAPTCHA manual, lalu melakukan checkout.
- Gambar CAPTCHA memakai latar bertekstur, garis bergelombang, dan karakter yang diputar satu per satu; jawaban tetap terikat ke sesi, akun, dan event serta gambar tidak di-cache browser.
- Saat PHPUnit berjalan, generator memakai token deterministik `QATEST` agar alur CAPTCHA hingga checkout mudah diuji. Token ini hanya aktif ketika aplikasi benar-benar berjalan di dalam test runner; lingkungan `local`, `staging`, dan `production` tetap membuat CAPTCHA acak seperti biasa.
- Stok dikurangi di dalam transaksi dengan `lockForUpdate()`. Pesanan menyimpan snapshot harga dan jumlah; event yang sudah memiliki pesanan tidak dapat dihapus.
- Email pemberitahuan dikirim saat admin menerbitkan event atau mengubah rincian utama event yang sudah terbit. Hanya akun yang memilih ikut menerima email yang diberi notifikasi; role admin tidak dikirim emailnya.
- Dashboard admin menampilkan ringkasan akun, aktivitas 5 menit terakhir, pesanan, pendapatan dari pesanan lunas, event yang sering dipesan, peserta paling aktif, dan pesanan terbaru.
- Status aktif dihitung dari permintaan terakhir yang terautentikasi dalam 5 menit terakhir; ini bukan indikator koneksi realtime.
- Pengguna/admin dapat mengganti password sendiri dengan memverifikasi password saat ini. Password akun lain tidak ditampilkan atau dapat dibaca admin.
- Setiap pesanan menahan stok selama maksimal 24 jam. Pesanan yang tidak dibayar kedaluwarsa dan stoknya dikembalikan oleh scheduler.
- Pembayaran tersedia melalui Midtrans Snap dan saldo internal. Pembayaran hanya berstatus lunas setelah callback server gateway diverifikasi; halaman redirect browser bukan bukti pembayaran.
- Top-up saldo menggunakan transfer manual: pengguna mengirim nominal dan referensi, lalu admin mencocokkan mutasi sebelum menyetujui. Persetujuan membuat entri ledger satu kali dan setiap perubahan saldo tercatat.
- Akun dengan riwayat transaksi dinonaktifkan, bukan dihapus, agar riwayat pembelian dan saldo tetap utuh. Akun tanpa riwayat dapat dihapus. Admin tidak dapat menonaktifkan diri atau menurunkan admin aktif terakhir.

## Migrasi

Pastikan `.env` sudah menunjuk ke database. Untuk pengujian pembelian bersamaan, gunakan MySQL XAMPP:

```dotenv
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=war_tiket
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Buat database `war_tiket` melalui phpMyAdmin, lalu jalankan:

```powershell
php artisan config:clear
php artisan migrate --seed
php artisan serve
```

Ekstensi `gd` PHP harus aktif untuk gambar CAPTCHA. Migrasi katalog mengubah tiket contoh lama menjadi event terbit yang bisa dicoba.

## Membuat admin

1. Daftar akun melalui aplikasi.
2. Dari folder proyek, promosikan akun dengan email yang didaftarkan:

   ```powershell
   php artisan war:make-admin email-anda@example.com
   ```

3. Masuk sebagai admin, buka tombol **Kelola Event**, lalu tekan **Buat event**.
4. Dashboard beranda `/admin` menunjukkan performa event dan pengguna. Gunakan **Event war** untuk CRUD event dan **Pengguna** untuk melihat status aktivitas, jumlah pesanan, dan tiket yang dipesan.
5. Isi nama, deskripsi, harga, stok, jadwal mulai/tutup. Centang **Terbitkan event** untuk menampilkan event ke katalog dan menjadwalkan email pengumuman.
6. Gunakan menu **Pengguna** untuk menambah akun, mengatur role, mengaktifkan/menonaktifkan akun, atau menghapus akun tanpa riwayat. Pengguna yang memiliki riwayat transaksi hanya dapat dinonaktifkan.
7. Gunakan menu **Verifikasi saldo** untuk mencocokkan permintaan top-up dengan mutasi transfer. Jangan menyetujui referensi yang belum terbukti masuk.

Pengguna umum tidak bisa memberikan role admin kepada dirinya sendiri.

Menu **Akun** membuka halaman ganti password. Password lama wajib benar dan password baru minimal 8 karakter.

## Konfigurasi email

Saat ini `.env` contoh menggunakan `MAIL_MAILER=log`, sehingga email hanya ditulis ke `storage/logs/laravel.log`. Untuk mengirim email sungguhan, atur `MAIL_MAILER=smtp`, host, port, username, password, enkripsi, dan alamat pengirim sesuai provider email. Jangan memasukkan kredensial email ke source code atau membagikannya.

Email pengumuman memakai antrean database. Jalankan worker di terminal terpisah selama aplikasi digunakan:

```powershell
php artisan queue:work
```

Di Windows/XAMPP, worker berhenti saat terminal ditutup. Untuk produksi, gunakan process manager/worker yang selalu hidup.

## Midtrans Snap sandbox

1. Buat akun sandbox Midtrans dan salin **Server Key** dari dashboard sandbox.
2. Isi `.env` lokal (jangan commit key):

   ```dotenv
   MIDTRANS_SERVER_KEY=SB-Mid-server-...
   MIDTRANS_CLIENT_KEY=SB-Mid-client-...
   MIDTRANS_IS_PRODUCTION=false
   ```

3. Jalankan `php artisan config:clear`.
4. Daftarkan notification URL di dashboard Midtrans sebagai `https://domain-publik-anda/midtrans/notification`. Callback memerlukan HTTPS dan dapat diakses internet; alamat `localhost`/`127.0.0.1` tidak bisa menerima webhook tanpa tunnel publik.
5. Untuk produksi, ganti ke server key produksi dan set `MIDTRANS_IS_PRODUCTION=true` hanya setelah pengujian sandbox selesai. Jangan gunakan key produksi pada komputer pengembangan atau membagikannya.

Untuk menampilkan rekening tujuan transfer saldo, isi `WALLET_BANK_NAME`, `WALLET_BANK_ACCOUNT_NAME`, dan `WALLET_BANK_ACCOUNT_NUMBER` di `.env`, kemudian jalankan `php artisan config:clear`. Jangan membuat permintaan transfer sebelum informasi tujuan rekening benar.

Snap dan metode pembayaran dikonfigurasi agar kedaluwarsa setelah 24 jam. Scheduler aplikasi memeriksa reservasi setiap menit. Pada Windows, jalankan di terminal terpisah saat aplikasi lokal digunakan:

```powershell
php artisan schedule:work
```

Untuk server produksi, aktifkan scheduler Laravel sesuai panduan deployment. Tanpa scheduler, pengunjung tetap memicu pengecekan saat membuka invoice, tetapi pelepasan stok terjadwal tidak berjalan otomatis.

## Pembayaran terlambat

Jika notifikasi pembayaran berhasil datang setelah stok pesanan dilepas, aplikasi tidak akan menerbitkan tiket kedua. Pesanan ditandai untuk pemeriksaan (`late_gateway_payment_at`) dan admin harus menghubungi pembeli untuk proses refund/penanganan di dashboard Midtrans. Pastikan expiry pembayaran Midtrans tetap 24 jam dan scheduler selalu berjalan.

## Uji

```powershell
php artisan test
php artisan route:list
```

CAPTCHA pada aplikasi ini ditujukan untuk verifikasi manual. Tidak ada integrasi pembaca CAPTCHA/OCR atau otomasi pengisian CAPTCHA.
