NAMA: M.FATKHUR ROZAQ
NIM: 101230058
KELAS: TF23A
MATA KULIAH: TECHNOPRENEURSHIP


# Warung Online 🏪

**Sistem POS & Manajemen Inventaris** untuk **Toko Pakdhe Snack** — dibangun dengan PHP native, MySQL, JavaScript, dan CSS.

---

## Fitur

- **Dashboard** — Ringkasan penjualan, stok menipis, transaksi terbaru
- **Manajemen Barang** — CRUD produk dengan harga jual & margin
- **Manajemen Stok** — Restok, riwayat harga beli, log stok, rekomendasi stok
- **Transaksi/POS** — Keranjang multi-item, pembayaran, hitung kembalian, cetak struk
- **Piutang** — Catat piutang pelanggan, jatuh tempo, pengingat via WhatsApp
- **Operasional** — Catat pengeluaran operasioanal per kategori
- **Laporan** — Laporan keuangan harian/mingguan/bulanan (penjualan, laba bersih, piutang)
- **Pengaturan** — Profil admin, pengaturan toko (nama, promo, no. WhatsApp)
- **Keamanan** — Login dengan CSRF token, brute-force protection, prepared statements

---

## Teknologi

| Bagian        | Teknologi                          |
|---------------|------------------------------------|
| Backend       | PHP 8+ (native, tanpa framework)   |
| Database      | MySQL (PDO)                        |
| Frontend      | Vanilla JavaScript, CSS3           |
| Autentikasi   | Session-based, password_verify()   |
| Keamanan      | Prepared statements, CSRF, XSS protection |

---

## Persyaratan Sistem

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- XAMPP / Laragon / server web sejenis
- Python 3.8+ (untuk AGENT.py)

---

## Instalasi

1. Clone repositori:
   ```bash
   git clone https://github.com/username/warung-online.git
   ```

2. Letakkan di folder web server (contoh: `C:\xampp\htdocs\warung-online`)

3. Buat database `warung_online` di phpMyAdmin / MySQL CLI

4. Import struktur database:
   ```bash
   mysql -u root -p warung_online < database.sql
   ```

5. Sesuaikan konfigurasi database di `config/database.php` jika perlu

6. Akses di browser:
   ```
   http://localhost/warung-online/pages/login.php
   ```

---

## Struktur Folder

```
warung-online/
├── api/              # JSON API endpoints
│   ├── barang.php
│   ├── dashboard.php
│   ├── laporan.php
│   ├── operasional.php
│   ├── piutang.php
│   ├── stok.php
│   └── transaksi.php
├── assets/
│   ├── css/
│   │   └── style1.css
│   └── js/
│       └── main.js
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   └── functions.php
├── pages/
│   ├── barang.php
│   ├── dashboard.php
│   ├── laporan.php
│   ├── login.php
│   ├── logout.php
│   ├── operasional.php
│   ├── piutang.php
│   ├── setting.php
│   ├── stok.php
│   └── transaksi.php
├── .github/
│   └── workflows/
│       └── ci.yml
├── AGENT.py          # AI development workflow tools & rules
├── AGENT.md          # AI development workflow rules
└── README.md
```

---

## Pengembangan

Proyek ini menggunakan **AGENT.py** sebagai alat bantu pengembangan untuk AI model.

**Siklus pengembangan wajib:**

```
Update Lokal → Test Lokal → Commit/Push → CI Auto-Test
```

Jalankan test sebelum push:

```bash
python AGENT.py --test
```

Lihat `AGENT.md` untuk aturan lengkap pengembangan.

---

## Lisensi

Hak cipta milik **Toko Pakdhe Snack**.
