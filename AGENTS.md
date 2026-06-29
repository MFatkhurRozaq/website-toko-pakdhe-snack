# AGENTS.md — Aturan untuk Semua Model AI

Dokumen ini berisi aturan yang **WAJIB dipatuhi** oleh semua model AI (termasuk opencode, GitHub Copilot, Cursor, dll.) dalam pengembangan dan pemeliharaan aplikasi **warung-online**.

---

## 1. Workflow Wajib: Update → Test → Push → CI → Iterasi

Setiap perubahan kode WAJIB mengikuti siklus berikut:

### Langkah 1: Develop & Test di Localhost
- Lakukan semua perubahan di lingkungan localhost (`C:\xampp\htdocs\warung-online`)
- **WAJIB test di localhost** setelah selesai melakukan perubahan
- Pastikan aplikasi berjalan tanpa error di `http://localhost/warung-online`
- Pastikan XAMPP (Apache + MySQL) menyala saat testing

### Langkah 2: Commit & Push ke GitHub
- Jika semua tes localhost **LOLOS**, lakukan:
  ```
  git add .
  git commit -m "deskripsi perubahan"
  git push origin main
  ```

### Langkah 3: Auto-Test di GitHub (CI/CD)
- Setelah push, sistem akan menjalankan tes otomatis di GitHub Actions / CI pipeline
- Pantau hasil tes di repositori GitHub

### Langkah 4: Analisis & Iterasi Jika Gagal
- Jika tes CI **GAGAL**:
  1. Analisa log error dari GitHub Actions
  2. Identifikasi penyebab kegagalan
  3. Perbaiki kode di localhost
  4. Ulangi dari **Langkah 1** hingga semua tes lolos

---

## 2. Pola Kode dari AGENT v3.0 (Wajib Ditiru)

Berdasarkan analisa `AGENT (1).py`, setiap komponen WAJIB mengikuti pola:

### 2.1 Modular & Terstruktur
- Setiap fitur dipisah dalam class/module sendiri
- Gunakan nama file dan class yang deskriptif
- Satu file = satu tanggung jawab utama

### 2.2 Error Handling
- Setiap operasi yang berpotensi gagal WAJIB punya try/catch
- Log error yang jelas
- Jangan pernah expose detail error ke user (hanya log internal)

### 2.3 Input Validation
- SEMUA input dari user WAJIB divalidasi
- Filter karakter berbahaya (SQL injection, XSS)
- Jangan pernah percaya input user

### 2.4 Logging & Audit Trail
- Setiap operasi penting WAJIB dicatat di log
- Gunakan format timestamp + action + status
- Log untuk debugging dan audit compliance

### 2.5 Dokumentasi Inline
- Setiap file WAJIB punya header dengan deskripsi
- Setiap class/fungsi WAJIB punya docstring
- Jelaskan parameter, return value, dan contoh penggunaan

### 2.6 Keamanan
- Jangan pernah hardcode credential di kode
- Gunakan prepared statement untuk SQL (PDO)
- Validasi session/auth di setiap halaman
- Sanitasi output sebelum ditampilkan

### 2.7 Testing
- Setiap komponen WAJIB punya unit test
- Tes mencakup: happy path, error path, edge cases
- Minimal test pass rate: 100%

---

## 3. Aturan PHP (untuk warung-online)

### 3.1 Database
- Selalu gunakan PDO dengan prepared statement
- Jangan gunakan mysql_* atau mysqli_query langsung
- Koneksi database hanya di `config/database.php`

### 3.2 Session & Auth
- Session_start() hanya di `config/database.php`
- Cek login di setiap halaman dengan `isLoggedIn()`
- Gunakan `password_hash()` dan `password_verify()` untuk password

### 3.3 Output
- Format rupiah dengan `formatRupiah()`
- Jangan campur logic PHP dan HTML di file yang sama
- Pisahkan API logic di folder `api/` dan tampilan di folder `pages/`

### 3.4 Error Reporting
- Selalu gunakan try/catch untuk operasi database
- Jangan tampilkan error detail ke user
- Log error untuk debugging

---

## 4. Aturan Commit & Git

### 4.1 Format Commit Message
```
<tipe>: <deskripsi singkat>

<opsional: penjelasan detail>
```

Tipe commit:
- `feat:` — Fitur baru
- `fix:` — Perbaikan bug
- `refactor:` — Refaktor kode
- `docs:` — Perubahan dokumentasi
- `test:` — Penambahan/ubah tes
- `chore:` — Tugas maintenance

### 4.2 Sebelum Commit
1. Jangan commit file konfigurasi lokal
2. Jangan commit credential/token/password
3. Jangan commit file yang tidak perlu (node_modules, .env, dll)
4. Pastikan `.gitignore` sudah benar

### 4.3 Setelah Commit
1. Push ke GitHub
2. Cek hasil CI pipeline
3. Jika gagal, segera perbaiki

---

## 5. Aturan untuk Model AI

### 5.1 Saat Memulai Tugas Baru
1. Baca file ini (AGENTS.md) — **WAJIB**
2. Baca `AGENT (1).py` untuk referensi pola kode
3. Pahami struktur direktori sebelum membuat perubahan
4. Identifikasi file apa yang perlu diubah

### 5.2 Saat Menulis Kode
1. Ikuti pola dari AGENT v3.0
2. Tambahkan error handling
3. Tambahkan logging untuk operasi penting
4. Pastikan tidak ada hardcode credential
5. Gunakan prepared statement untuk semua query

### 5.3 Setelah Selesai
1. **WAJIB test di localhost**
2. Jika lolos → commit & push
3. Jika gagal → perbaiki dan test ulang
4. Pantau hasil CI di GitHub
5. Jika CI gagal → analisa dan iterasi

---

## 6. Referensi

### Struktur Direktori
```
warung-online/
├── AGENT (1).py       # Referensi pola kode AI agent
├── AGENTS.md          # Aturan ini
├── api/               # Backend API endpoints (PHP)
├── assets/            # CSS, JS, gambar
├── config/            # Konfigurasi (database.php)
├── includes/          # Fungsi bersama (auth, functions)
└── pages/             # Halaman tampilan (PHP + HTML)
```

### File Penting
- `config/database.php` — Koneksi database & session
- `includes/auth.php` — Fungsi login/logout
- `includes/functions.php` — Fungsi utility

---

## 7. Penegakan Aturan

- Setiap model AI WAJIB membaca AGENTS.md sebelum bekerja
- Pelanggaran aturan keamanan = PRIORITAS TINGGI untuk diperbaiki
- Semua perubahan WAJIB melalui siklus: **Develop → Test → Commit → Push → CI → Iterasi**
- Jika ragu, tanya user sebelum membuat perubahan besar
