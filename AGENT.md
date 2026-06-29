# AI Model Development Workflow Rules

> Aturan wajib untuk semua AI model dalam pengembangan & pemeliharaan aplikasi **Warung Online**.

---

## Siklus Utama (WAJIB)

```
Update Lokal → Test Lokal → Push/Commit → CI Auto-Test
    ↕                                  ↕
Analisa & Perbaiki ←── Gagal ←── (jika gagal)
```

---

## R1 — Update di Localhost

Setelah melakukan perubahan kode, **WAJIB simpan semua file** terlebih dahulu.

**Larangan:** Jangan push, jangan commit, jangan deploy — sebelum test lokal lolos.

---

## R2 — Test Lokal (WAJIB Sebelum Push)

Langkah-langkah test lokal:

1. Buka terminal di direktori proyek (`C:\xampp\htdocs\warung-online`)
2. Jalankan perintah:
   ```bash
   python AGENT.py --test
   ```
3. Tunggu hingga seluruh test selesai (24 test)
4. Periksa output: `[PASS] ALL TESTS PASSED - PRODUCTION-READY`
5. **JIKA GAGAL** → jangan lanjut ke R3, ulangi R1 (perbaiki kode)

---

## R3 — Lolos Test Lokal → Commit & Push ke GitHub

Hanya jika R2 lolos (`[PASS] ALL TESTS PASSED`), lakukan:

```bash
git add .
git commit -m "<deskripsi perubahan yang JELAS>"
git push
```

**Format pesan commit:**

| Prefix     | Keterangan              |
|------------|-------------------------|
| `feat:`    | Fitur baru              |
| `fix:`     | Perbaikan bug           |
| `update:`  | Perubahan/peningkatan   |
| `refactor:`| Refaktor kode           |
| `docs:`    | Dokumentasi             |
| `test:`    | Perubahan test          |
| `chore:`   | Tugas teknis            |

Jangan tinggalkan terminal — pantau hasil push.

---

## R4 — Setelah Push → CI Auto-Test di GitHub

Setelah `git push`:

1. GitHub Actions akan otomatis menjalankan workflow CI
2. Buka tab **Actions** di repositori GitHub untuk memantau
3. Tunggu sampai workflow selesai (~1-2 menit)
4. Periksa status workflow:
   - **SUCCESS** → selesai, tidak ada tindakan lanjutan
   - **FAILURE** → lanjut ke R5

---

## R5 — CI Gagal → Analisa & Test Ulang

Jika CI workflow di GitHub gagal:

1. Buka workflow yang gagal di **GitHub Actions**
2. Klik job yang gagal untuk melihat **log error**
3. Baca log dengan saksama — cari penyebab kegagalan:
   - **Test failure** — assertion error, test tidak sesuai
   - **Syntax/runtime error** — kode bermasalah
   - **Dependency error** — package hilang/versi salah
   - **Environment error** — konfigurasi CI berbeda dengan lokal
4. Catat **error message** dan **line number** yang disebutkan
5. Perbaiki kode di localhost (kembali ke R1)
6. Ulangi siklus: **R1 → R2 → R3 → R4**
7. Ulangi sampai CI workflow **SUCCESS**

---

## R6 — Larangan Skip Langkah

**DILARANG KERAS** melewati/melompati langkah dalam siklus:

```
Update Lokal → Test Lokal → Commit/Push → CI Auto-Test → (jika gagal) Analisa
```

- Setiap perubahan **WAJIB** melewati seluruh siklus ini
- Tidak ada pengecualian
- Tidak ada "saya yakin ini aman" tanpa test

---

## R7 — Dokumentasi Perubahan

Setiap commit **WAJIB** menyertakan keterangan yang jelas tentang:

- **Apa** yang diubah (deskripsi teknis)
- **Mengapa** diubah (alasan/bug/permintaan)
- **Dampak** perubahan (risiko, efek samping)

**Contoh commit message yang baik:**

```
fix(api): perbaiki perhitungan total transaksi saat diskon
update(stok): tambah filter tanggal pada riwayat stok
```

---

## R8 — CI Workflow Tidak Boleh Dihapus/Dimatikan

File `.github/workflows/ci.yml` adalah **bagian integral** dari siklus pengembangan.

**DILARANG:**

- Menghapus file workflow CI
- Menonaktifkan GitHub Actions di repositori
- Mematikan required status checks (jika diaktifkan)
- Push tanpa melewati CI (kecuali force majeure)

---

## Referensi

- Aturan ini juga tersedia dalam bentuk komentar di `AGENT.py` (Part 0)
- CI workflow: `.github/workflows/ci.yml`
- Test command: `python AGENT.py --test`
