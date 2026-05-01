# 📋 Planning Development: SyIAR Gemilang
## (Sistem Informasi Al-Azhar Rumah Gemilang)

---

## 🎯 1. Overview Proyek
| Item | Deskripsi |
|------|-----------|
| **Nama Aplikasi** | SyIAR Gemilang |
| **Arsitektur** | Decoupled Full-Stack: CI4 sebagai REST API Backend + Express.js sebagai SSR Frontend |
| **Fokus Fase 1** | 1. Sistem Role & Permission Dinamis (RBAC)<br>2. Modul Penilaian Siswa/Santri |
| **Target Pengguna** | Super Admin, Admin Akademik, Guru/Asatidz, Kepala Bagian |
| **Lingkungan** | Node.js 18+/20+, PHP 8.1+, MySQL 8.x, npm/composer |

---

## 📌 Status Implementasi Saat Ini (v1.0.0)

| Komponen | Status | Keterangan |
|----------|--------|-------------|
| **CI4 Backend** | ✅ Siap | REST API dengan JWT authentication |
| **Users Table** | ✅ Siap | Migration + Model ada |
| **RBAC Tables** | ✅ Siap | roles, permissions, role_permissions, user_roles semua siap |
| **Auth API** | ✅ Siap | Endpoint `/api/auth/login` berfungsi |
| **RBAC Seeder** | ✅ Siap | Data role & permission awal |
| **Express Frontend** | ❌ Belum | Belum diimplementasikan |
| **Admin UI (RBAC)** | ❌ Belum | Belum diimplementasikan |

---

## 🛠 2. Arsitektur & Tech Stack
| Layer | Teknologi | Peran |
|-------|-----------|-------|
| **Frontend Server** | Express.js + EJS/Handlebars | Routing, SSR, UI Rendering, Session Management, API Consumer |
| **Styling** | Tailwind CSS (via PostCSS) | Utility-first CSS, custom components, responsive design |
| **Backend API** | CodeIgniter 4 (RESTful) | Business Logic, Database Access, Validation, JWT/Session Auth, JSON Response |
| **Database** | MySQL 8.x / MariaDB | Relational data storage, ACID transactions |
| **HTTP Client** | Axios / Node `fetch` | Komunikasi Express → CI4 |
| **State/Cache** | Redis / Memory Cache (CI4) | Caching permission mapping & session token |
| **Build Tool** | Vite / npm scripts | Compile Tailwind, optimize assets, watch mode |

**🔁 Alur Data:**
```
Client → Express.js (Route + EJS + Tailwind) → Axios → CI4 REST API → MySQL
                                      ↑                              ↓
                              Cookie/Token Auth ←────────── JSON Response
```

---

## 🗄 3. Desain Database (MySQL)
*(Skema tetap fokus pada RBAC & Penilaian. CI4 akan mengakses tabel ini langsung.)*

| Tabel | Fungsi Utama |
|-------|--------------|
| `users` | Akun pengguna, password hash, status |
| `roles` | Master role (admin, guru, wali, dll) |
| `permissions` | Hak akses granular (`modul.create`, `modul.read`, dll) |
| `role_permissions` | Mapping many-to-many role ↔ permission |
| `user_roles` | Mapping many-to-many user ↔ role |
| `santri` | Data master siswa (NIS, kelas, status) |
| `kategori_penilaian` | Kategori besar (Akhlak, Tahfidz, Akademik) |
| `aspek_penilaian` | Sub-aspek dinamis + skala min/max + bobot |
| `penilaian` | Transaksi nilai per santri, aspek, pengajar, tanggal |
| `log_aktivitas` | Audit trail (login, ubah nilai, hapus data) |

> 💡 **Catatan:** Gunakan CI4 Migrations untuk deployment skema. Aktifkan `softDeletes` pada `users` & `santri`. Index pada `NIS`, `role_id`, `santri_id`, `aspek_id`.

---

## 🔐 4. Sistem Role & Permission (RBAC Dinamis)

### 4.1. Konsep
- Role & Permission **100% dinamis** via UI Admin.
- Format permission: `modul.aksi` (contoh: `penilaian.create`, `master.santri.export`).
- Satu user boleh memiliki **multi-role** (union permission).
- Validasi dilakukan di **2 layer**:
  1. **Express.js**: Middleware route guard (menyembunyikan menu/route sebelum render)
  2. **CI4 API**: Filter/API Guard (memvalidasi token & permission sebelum eksekusi query)

### 4.2. Struktur Permission Table
```sql
permissions
  id (PK) | kode (VARCHAR, UNIQUE) | modul (VARCHAR) | aksi (VARCHAR) | deskripsi (TEXT)
```

### 4.3. Alur Autentikasi & Otorisasi
1. User login di Express → Express hit `POST /api/auth/login` ke CI4.
2. CI4 validasi → return `{ token: "JWT/SessionID", user: {...}, permissions: [...] }`.
3. Express simpan `token` di `httpOnly cookie`, simpan `permissions` di session.
4. Express middleware `requirePermission('penilaian.create')` cek session sebelum render route.
5. Setiap request Express → CI4 menyertakan `Authorization: Bearer <token>` di header.
6. CI4 `AuthFilter` decode token & cek `hasPermission()` di DB/cache.

---

## 📝 5. Modul Penilaian Siswa/Santri

### 5.1. Fitur UI (Express + Tailwind)
| Fitur | Komponen Tailwind/JS |
|-------|----------------------|
| Dashboard Guru | Card grid, Chart.js (progres nilai), quick-access button |
| Input Penilaian | Form dinamis, `input[type=range]`/`number`, live validation, draft auto-save |
| Rekap & Export | DataTables + Tailwind table, export CSV/Excel via CI4 endpoint |
| Manajemen Aspek | Modal CRUD, drag-drop urutan (opsional), toggle aktif/nonaktif |

### 5.2. API Endpoint CI4 (Contoh)
| Method | Endpoint | Deskripsi | Auth Required |
|--------|----------|-----------|---------------|
| `GET` | `/api/santri` | List santri (filter kelas/status) | ✅ |
| `GET` | `/api/penilaian/aspek/:kategori_id` | Fetch aspek aktif | ✅ |
| `POST` | `/api/penilaian/submit` | Simpan nilai batch | ✅ `penilaian.create` |
| `GET` | `/api/penilaian/rekap?periode=2024-2` | Rekap nilai + bobot | ✅ `penilaian.read` |
| `GET` | `/api/laporan/export/excel` | Download file | ✅ `penilaian.export` |

---

## 🎨 6. Integrasi Express.js + Tailwind CSS

### 6.1. Struktur Folder Frontend
```
frontend/
├── src/
│   ├── views/          # EJS templates (layout, pages, partials)
│   ├── public/         # Static assets (compiled CSS, images, JS)
│   ├── routes/         # Express routers (auth, dashboard, penilaian, admin)
│   ├── middleware/     # auth.js, permission.js, error.js
│   ├── services/       # apiClient.js (Axios instance with token injection)
│   └── utils/          # formatter.js, validator.js
├── tailwind.config.js
├── postcss.config.js
├── package.json
└── server.js
```

### 6.2. Setup Tailwind di Express
```bash
# 1. Inisialisasi
cd frontend && npm init -y
npm install express ejs axios dotenv
npm install -D tailwindcss postcss autoprefixer concurrently

# 2. Init Tailwind
npx tailwindcss init -p
```

**`tailwind.config.js`**
```js
module.exports = {
  content: ["./src/**/*.{ejs,js}", "./public/**/*.{js,css}"],
  theme: { extend: { colors: { primary: "#0f766e", secondary: "#f59e0b" } } },
  plugins: [],
}
```

**`postcss.config.js`**
```js
module.exports = {
  plugins: [ require('tailwindcss'), require('autoprefixer') ],
}
```

**`package.json` scripts**
```json
"scripts": {
  "build:css": "tailwindcss -i ./src/styles.css -o ./public/css/main.css --minify",
  "dev:css": "tailwindcss -i ./src/styles.css -o ./public/css/main.css --watch",
  "start": "node server.js",
  "dev": "concurrently \"nodemon server.js\" \"npm run dev:css\""
}
```

---

## 🌐 7. Alur Komunikasi & Security Flow

| Layer | Mekanisme Keamanan |
|-------|-------------------|
| **Express → Client** | `res.cookie('syiar_token', token, { httpOnly: true, secure: true, sameSite: 'strict' })` |
| **Express → CI4** | Axios interceptor attach `headers: { Authorization: Bearer ${token} }` |
| **CI4 Auth** | `Config/Filters.php` → `AuthFilter` validasi JWT → `PermissionFilter` cek DB/Cache |
| **CI4 → DB** | Query Builder + Prepared Statements, `strict mode` aktif |
| **CORS** | `Config/CORS.php` di CI4: `allowedOrigins: ['http://localhost:3000']`, `allowedHeaders: ['Authorization', 'Content-Type']` |

---

## 📅 8. Rencana Pengembangan (Phases & Timeline)

| Fase | Durasi | Deliverable | Target | Status |
|------|--------|-------------|--------|--------|
| **Phase 0** | - | Perbaiki migration - tambahkan tabel `user_roles` | Fix RBAC schema | ✅ selesai |
| **Phase 1** | Minggu 1 | - Setup CI4 API + CORS + JWT<br>- Setup Express + EJS + Tailwind build<br>- DB Migration awal | Infrastruktur siap, login flow working | 🟡 dalam progress |
| **Phase 2** | Minggu 2 | - CRUD Role & Permission (UI + API)<br>- Middleware `checkPermission` di Express & CI4<br>- Menu dinamis berdasarkan role | RBAC dinamis 100% fungsional | ⚪ pending |
| **Phase 3** | Minggu 3 | - CRUD Master Santri + Import CSV<br>- CRUD Kategori & Aspek Penilaian | Data master siap, validasi skala aktif | ⚪ pending |
| **Phase 4** | Minggu 4 | - Form Input Penilaian (batch + draft)<br>- Rekap, Chart progres, Export Excel | Modul penilaian siap produksi | ⚪ pending |
| **Phase 5** | Minggu 5-6 | - UI Polish (Tailwind components, loading states)<br>- Log aktivitas, error handling global<br>- Testing, dokumentasi, deployment | Release v1.0 stable | ⚪ pending |

---

## 🔒 9. Keamanan & Best Practices

| Aspek | Implementasi |
|-------|--------------|
| **Auth Token** | JWT (exp 2h) + Refresh Token, disimpan di `httpOnly` cookie |
| **CSRF** | Express `csurf` middleware + CI4 CSRF filter untuk form submit |
| **Rate Limiting** | `express-rate-limit` pada endpoint `/api/auth/*` |
| **Input Sanitization** | `validator.js` di Express, CI4 `Validation` library di backend |
| **Error Handling** | Global `try/catch`, custom error response JSON, log ke `writable/logs/` |
| **Backup** | `mysqldump` cron + Express backup trigger via admin UI |
| **Environment** | `.env` terpisah: `frontend/.env`, `backend/.env` (JANGAN commit!) |

---

## 🚀 10. Langkah Setup Awal (Quick Start)

### 10.1. Backend (CodeIgniter 4)
```bash
composer create-project codeigniter4/appstarter backend
cd backend
cp env .env
# Edit .env: CI_ENVIRONMENT = development, database credentials
php spark serve --port 8080
```
Aktifkan CORS di `Config/CORS.php` & buat controller API di `app/Controllers/Api/`.

### 10.2. Frontend (Express + Tailwind)
```bash
mkdir frontend && cd frontend
# Ikuti panduan setup Tailwind di Section 6.2
npm install
npm run dev  # Mulai Express (port 3000) + Tailwind watch
```

### 10.3. Integrasi Awal
1. Express buat route `/login` → POST ke `http://localhost:8080/api/auth/login`
2. Simpan token → Redirect ke `/dashboard`
3. Dashboard fetch `http://localhost:8080/api/user/permissions` → Render menu dinamis

---

## 📎 Lampiran & Fase Lanjutan
- Portal Wali Santri (view-only via token scoped)
- Presensi QR/RFID integration
- Export Rapor PDF (dompdf/mpdf via CI4)
- Notifikasi WhatsApp Gateway (Fonnte/Wablas API)
- CI/CD Pipeline (GitHub Actions → VPS/Hosting)

---
> 📝 **Dokumen ini bersifat living document.** Update saat requirement berubah atau hasil review testing.  
> **Arsitektur**: Express.js (SSR + Tailwind) ↔ CI4 REST API ↔ MySQL  
> **Versi**: `1.1.0` | **Status**: `Planning Approved` | **Tanggal**: `2024`
```

### 💡 Catatan Penting untuk Implementasi:
1. **Token Flow**: Gunakan JWT yang di-generate CI4, lalu Express menyimpannya sebagai `httpOnly cookie`. Setiap request Express ke CI4 akan otomatis menyertakan token via Axios interceptor. Ini aman dari XSS & mudah dikelola di SSR.
2. **Tailwind Build**: Jangan gunakan CDN Tailwind untuk produksi. Gunakan build step (`tailwindcss -i ... -o ...`) agar file CSS hanya berisi utility yang benar-benar dipakai (tree-shaking otomatis).
3. **CI4 sebagai API**: Matikan CSRF untuk endpoint `/api/*`, atau gunakan token-based CSRF. Pastikan response selalu JSON: `return $this->response->setJSON([...])`.
4. **Middleware Permission**: Buat helper di Express:
   ```js
   // middleware/permission.js
   module.exports = (perm) => (req, res, next) => {
     const userPerms = req.session?.permissions || [];
     if (userPerms.includes(perm)) return next();
     req.flash('error', 'Akses ditolak');
     res.redirect('/dashboard');
   };
   ```

