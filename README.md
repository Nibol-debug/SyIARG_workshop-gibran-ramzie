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

| Fase | Durasi | Deliverable | Target |
|------|--------|-------------|--------|
| **Phase 1** | Minggu 1 | - Setup CI4 API + CORS + JWT<br>- Setup Express + EJS + Tailwind build<br>- DB Migration awal | Infrastruktur siap, login flow working |
| **Phase 2** | Minggu 2 | - CRUD Role & Permission (UI + API)<br>- Middleware `checkPermission` di Express & CI4<br>- Menu dinamis berdasarkan role | RBAC dinamis 100% fungsional |
| **Phase 3** | Minggu 3 | - CRUD Master Santri + Import CSV<br>- CRUD Kategori & Aspek Penilaian | Data master siap, validasi skala aktif |
| **Phase 4** | Minggu 4 | - Form Input Penilaian (batch + draft)<br>- Rekap, Chart progres, Export Excel | Modul penilaian siap produksi |
| **Phase 5** | Minggu 5-6 | - UI Polish (Tailwind components, loading states)<br>- Log aktivitas, error handling global<br>- Testing, dokumentasi, deployment | Release v1.0 stable |

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

## � 11. Work Plan & Progress Tracking

### 11.1 Project Timeline Overview
```
MAY 2026
├── Week 1 (May 1-7)   : PHASE 1 - Setup Infrastruktur ✅ [IN PROGRESS]
├── Week 2 (May 8-14)  : PHASE 2 - RBAC Dinamis 
├── Week 3 (May 15-21) : PHASE 3 - Master Data
├── Week 4 (May 22-28) : PHASE 4 - Modul Penilaian
└── Week 5-6 (May 29+) : PHASE 5 - Polish & Release

JUNI 2026
└── Week 7+ : Maintenance & Fase Lanjutan
```

### 11.2 Detailed Work Plan (Sprint-based)

#### 🔵 PHASE 1: Setup Infrastruktur (Week 1) - IN PROGRESS
| Task ID | Task Name | Status | Assignee | Target Date | Notes |
|---------|-----------|--------|----------|-------------|-------|
| P1-T1 | Initialize CI4 Backend + `.env` config | `NOT STARTED` | Backend Dev | May 3 | Install composer deps, setup database credentials |
| P1-T2 | Configure CORS & JWT Auth (CI4) | `NOT STARTED` | Backend Dev | May 4 | `Config/CORS.php`, create `AuthController.php` |
| P1-T3 | Database Migration - Create core tables | `NOT STARTED` | Backend Dev | May 5 | roles, permissions, users, role_permissions, user_roles, santri, log_aktivitas |
| P1-T4 | Initialize Express.js Frontend + Tailwind | `NOT STARTED` | Frontend Dev | May 3 | Setup package.json, tailwind.config.js, postcss.config.js, src/ folder structure |
| P1-T5 | Setup Tailwind build pipeline (watch + minify) | `NOT STARTED` | Frontend Dev | May 4 | Configure build scripts, test CSS compilation |
| P1-T6 | Create Express base routes & EJS layout templates | `NOT STARTED` | Frontend Dev | May 5 | `/login`, `/dashboard`, base layout dengan Tailwind styling |
| P1-T7 | Integrate Login Flow (Express ↔ CI4 API) | `NOT STARTED` | Both | May 6 | Test token generation, httpOnly cookie, session management |
| P1-T8 | Setup Axios interceptor + API client | `NOT STARTED` | Frontend Dev | May 6 | Auto-inject Bearer token, error handling |
| P1-T9 | Test end-to-end login & token validation | `PLANNED` | Both | May 7 | QA testing: login success, invalid creds, token expiry |

**Deliverable P1:** ✅ Kedua stack jalan, login berfungsi, token flow tested

---

#### 🔵 PHASE 2: RBAC Dinamis (Week 2) - PLANNED
| Task ID | Task Name | Status | Assignee | Target Date | Notes |
|---------|-----------|--------|----------|-------------|-------|
| P2-T1 | Create Role & Permission CRUD API (CI4) | `PLANNED` | Backend Dev | May 10 | `Api/RoleController`, `Api/PermissionController` with validation |
| P2-T2 | Implement Permission seeding (starter data) | `PLANNED` | Backend Dev | May 10 | Seeder untuk 20+ permissions dasar (create, read, update, delete, export per modul) |
| P2-T3 | Build Permission Middleware di CI4 | `PLANNED` | Backend Dev | May 11 | `Filters/PermissionFilter` - validate token + check permission |
| P2-T4 | Create Admin UI - Role Management page | `PLANNED` | Frontend Dev | May 11 | List roles, form add/edit, delete with confirmation, table search |
| P2-T5 | Create Admin UI - Permission Management page | `PLANNED` | Frontend Dev | May 11 | List permissions, assign permissions to roles (checkbox grid), bulk operations |
| P2-T6 | Build Permission Guard Middleware (Express) | `PLANNED` | Frontend Dev | May 12 | Hide/show menu items, protect routes based on session permissions |
| P2-T7 | Dynamic Menu Rendering (Express template) | `PLANNED` | Frontend Dev | May 12 | Fetch user permissions → render sidebar/navbar conditionally |
| P2-T8 | Test RBAC flow end-to-end | `PLANNED` | Both | May 13 | Create multi-role users, test permission inheritance, menu visibility |
| P2-T9 | Cache permission mapping (optional Redis) | `PLANNED` | Backend Dev | May 14 | Improve performance - cache `user → role → permission` mapping |

**Deliverable P2:** ✅ Role & Permission 100% dinamis, middleware protection, menu berbasis akses

---

#### 🔵 PHASE 3: Master Data (Week 3) - PLANNED
| Task ID | Task Name | Status | Assignee | Target Date | Notes |
|---------|-----------|--------|----------|-------------|-------|
| P3-T1 | Database Migration - Santri + Penilaian tables | `PLANNED` | Backend Dev | May 17 | kategori_penilaian, aspek_penilaian tables dengan soft deletes |
| P3-T2 | Create Master Santri CRUD API (CI4) | `PLANNED` | Backend Dev | May 17 | POST, GET, UPDATE, DELETE santri (NIS, nama, kelas, status) |
| P3-T3 | Implement CSV Import API (santri bulk) | `PLANNED` | Backend Dev | May 18 | Parse CSV, validate, insert batch dengan error reporting |
| P3-T4 | Create Kategori & Aspek Penilaian API (CI4) | `PLANNED` | Backend Dev | May 18 | CRUD kategori (Akhlak, Tahfidz, Akademik), aspek dinamis |
| P3-T5 | Build Master Santri UI (Express) | `PLANNED` | Frontend Dev | May 19 | List santri (pagination, filter), add/edit/delete form, import CSV button |
| P3-T6 | Build Aspek Penilaian Management UI (Express) | `PLANNED` | Frontend Dev | May 20 | Modal CRUD, drag-drop urutan, toggle aktif/nonaktif, preview skala |
| P3-T7 | Data validation & error handling | `PLANNED` | Both | May 20 | Client-side (Express) + server-side (CI4) validation |
| P3-T8 | Populate test data & QA | `PLANNED` | QA | May 21 | Create 50+ santri, multiple aspek per kategori, verify DB consistency |

**Deliverable P3:** ✅ Master data siap, import CSV berfungsi, aspek dinamis tested

---

#### 🔵 PHASE 4: Modul Penilaian (Week 4) - PLANNED
| Task ID | Task Name | Status | Assignee | Target Date | Notes |
|---------|-----------|--------|----------|-------------|-------|
| P4-T1 | Create Input Penilaian API (CI4) | `PLANNED` | Backend Dev | May 24 | POST batch penilaian, validasi range nilai, duplikat checking |
| P4-T2 | Implement Draft-save functionality (API) | `PLANNED` | Backend Dev | May 24 | Store incomplete submissions, allow resume later (is_draft flag) |
| P4-T3 | Create Rekap & Export API (CI4) | `PLANNED` | Backend Dev | May 25 | GET aggregate nilai + bobot per santri, export CSV/Excel generator |
| P4-T4 | Build Dashboard Guru (Express) | `PLANNED` | Frontend Dev | May 26 | Card grid overview, quick-stats (total santri, submitted, pending), recent activity |
| P4-T5 | Build Input Penilaian Form (Express) | `PLANNED` | Frontend Dev | May 26 | Dynamic form per aspek (input range/number), live validation, draft auto-save setiap 30s |
| P4-T6 | Build Rekap & Report UI (Express) | `PLANNED` | Frontend Dev | May 27 | DataTables display, filter per kelas/periode, export buttons, print preview |
| P4-T7 | Integrate Chart.js - Penilaian visualization | `PLANNED` | Frontend Dev | May 27 | Progress bar per aspek, distribusi nilai histogram, per-santri sparkline |
| P4-T8 | End-to-end penilaian flow testing | `PLANNED` | QA | May 28 | Input nilai → draft save → submit → rekap → export, verify calculations |

**Deliverable P4:** ✅ Modul penilaian fully functional, UI complete, export working

---

#### 🔵 PHASE 5: Polish & Release (Week 5-6) - PLANNED
| Task ID | Task Name | Status | Assignee | Target Date | Notes |
|---------|-----------|--------|----------|-------------|-------|
| P5-T1 | Global Error Handling & Logging | `PLANNED` | Both | Jun 1 | Express error middleware, CI4 exception handling, structured logs |
| P5-T2 | Implement Activity Audit Trail (log_aktivitas) | `PLANNED` | Backend Dev | Jun 1 | Log all create/update/delete, user login/logout events |
| P5-T3 | Tailwind component polish & consistency | `PLANNED` | Frontend Dev | Jun 2 | Ensure consistent spacing, colors, buttons, modals across all pages |
| P5-T4 | Loading states & skeleton screens | `PLANNED` | Frontend Dev | Jun 2 | Improve UX during API calls, show loaders instead of blank screen |
| P5-T5 | Form validation messages & tooltips | `PLANNED` | Frontend Dev | Jun 2 | User-friendly error messages, inline field validation feedback |
| P5-T6 | Performance optimization (minify, compress) | `PLANNED` | Both | Jun 3 | Compress static assets, optimize DB queries, implement pagination |
| P5-T7 | Security hardening (rate limiting, input sanitization) | `PLANNED` | Both | Jun 3 | Add `express-rate-limit`, validation on all inputs, helmet.js middleware |
| P5-T8 | Documentation & code comments | `PLANNED` | Both | Jun 4 | API documentation, code inline comments, deployment guide |
| P5-T9 | Comprehensive QA & UAT | `PLANNED` | QA + PO | Jun 5-6 | Functional testing, browser compatibility, performance testing, user acceptance |
| P5-T10 | Production deployment (VPS/Cloud) | `PLANNED` | DevOps | Jun 6 | Setup server, CI/CD pipeline (GitHub Actions), database backup, SSL/TLS |

**Deliverable P5:** ✅ v1.0 Production-ready, fully tested, deployed live

---

### 11.3 Resource Allocation
| Role | Count | Responsibility |
|------|-------|-----------------|
| **Backend Developer** | 1 | CI4 API, database, auth, business logic |
| **Frontend Developer** | 1 | Express.js, Tailwind, UI/UX, form handling |
| **QA/Tester** | 1 | Testing, bug tracking, UAT coordination |
| **DevOps/Infra** | 0.5 | Server setup, CI/CD, deployment (part-time) |
| **Project Manager** | 0.5 | Progress tracking, stakeholder communication |

---

### 11.4 Critical Path & Dependencies
```
P1-T1 (CI4 setup) ──→ P1-T2 (CORS) ──→ P1-T3 (DB migration)
                                      ↓
                      P2-T1 (RBAC API) ──→ P3-T1 (Master data tables)
                                            ↓
                                      P4-T1 (Penilaian API)

P1-T4 (Express setup) ──→ P1-T5 (Tailwind) ──→ P1-T6 (Base routes)
                                                ↓
                           P2-T4 (Admin UI) ──→ P3-T5 (Master UI)
                                                ↓
                                          P4-T5 (Penilaian UI)

P1-T7 (Login integration) ──→ P1-T9 (E2E test)
↑ Blocks: P2-T1, P3-T1, P4-T1
```

---

### 11.5 Risk Assessment & Mitigation
| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|-----------|
| Database schema design changes mid-development | Medium | High | Lock schema in P1-T3, review with team, use CI4 migrations for versioning |
| JWT token expiry issues in production | Medium | Medium | Implement refresh token endpoint early, test token lifecycle thoroughly in P2 |
| Tailwind CSS bloat / slow build times | Low | Low | Use content config, tree-shake unused CSS, monitor build performance |
| CORS/auth flow bugs delaying integration | Medium | High | Do P1-T7 early, create mock API endpoints for parallel frontend development |
| Performance degradation with large datasets | Low | High | Implement pagination (P3), caching (P2-T9), query optimization (P4) |
| Team member unavailability | Medium | High | Cross-train on CI4 & Express, document code practices, maintain wiki/runbooks |

---

### 11.6 Progress Tracking (Weekly Updates)
Update section ini setiap akhir minggu dengan:
- ✅ Completed tasks
- 🟡 In-progress tasks  
- ❌ Blocked tasks (dengan alasan)
- 📊 % Phase completion
- 📝 Notes & blockers untuk sprint berikutnya

**Template Weekly Status:**
```
### Week [N] Status Report ([DATE])
- **Phase**: [PHASE_NAME]
- **Overall Progress**: X% complete
- **Completed**: [list P?-T? IDs]
- **In Progress**: [list P?-T? IDs]
- **Blocked**: [list P?-T? IDs with reasons]
- **Next Week Focus**: [top 3 priorities]
- **Risks Emerging**: [if any]
- **Budget/Resources**: [on track / needs adjustment]
```

---

### 11.7 Success Criteria (Definition of Done per Phase)

**Phase 1 Done When:**
- ✅ Both backend & frontend running locally
- ✅ Database initialized with seed data
- ✅ Login flow E2E tested (success & failure cases)
- ✅ Token generation & validation working
- ✅ CORS configured correctly (no origin errors)

**Phase 2 Done When:**
- ✅ Admin can CRUD roles & permissions via UI
- ✅ User menu dynamically renders based on permission
- ✅ Protected routes redirect to /login when permission denied
- ✅ Multi-role user inheritance tested
- ✅ Permission caching working (if implemented)

**Phase 3 Done When:**
- ✅ 100+ santri imported via CSV without errors
- ✅ Master data CRUD operations stable
- ✅ Aspek penilaian fully configurable
- ✅ Data validation (both sides) working
- ✅ Test data populated for penilaian flow testing

**Phase 4 Done When:**
- ✅ Input penilaian form functional (all aspek, dynamic)
- ✅ Draft-save working (auto-save every 30s)
- ✅ Submit penilaian validating & storing correctly
- ✅ Rekap values calculated correctly (bobot applied)
- ✅ Export to CSV/Excel working
- ✅ Dashboard stats & charts rendering

**Phase 5 Done When:**
- ✅ All error scenarios handled gracefully
- ✅ Activity logs populated (audit trail complete)
- ✅ UI consistent & polished (Tailwind components)
- ✅ Performance acceptable (<2s page load)
- ✅ Security audit passed (input sanitization, rate limiting, etc.)
- ✅ Full UAT sign-off from stakeholders
- ✅ Deployed to production & tested live
- ✅ Runbook & disaster recovery plan documented

---
## 🧪 12. Testing Strategy & Procedures

### 12.1 Testing Framework Setup

#### Backend (CI4) - PHPUnit
```bash
cd backend

# Install PHPUnit (sudah di composer.json)
composer install

# Konfigurasi testing DB
cp phpunit.xml.dist phpunit.xml
# Edit phpunit.xml: gunakan DB test terpisah (mysql://user:pass@localhost/syiar_test)

# Setup test database
php spark migrate --all
```

#### Frontend (Express) - Jest + Supertest
```bash
cd frontend

# Install testing dependencies
npm install -D jest supertest @testing-library/react

# Buat jest.config.js
npx jest --init

# Run tests
npm test
```

---

### 12.2 Testing Strategy Pyramid

```
        🤖 UI/E2E Tests (5%)
       ├─ Playwright, Selenium
       ├─ Full user flows
       └─ Integration scenarios
       
    📊 Integration Tests (15%)
   ├─ API tests (Postman/Thunder)
   ├─ Database transactions
   └─ Multi-component flows
   
  🧩 Unit Tests (80%)
 ├─ Model tests
 ├─ Controller tests
 ├─ Service/Helper tests
 └─ Validation tests
```

---

### 12.3 Unit Testing Examples

#### 12.3.1 CI4 Backend Tests

**Test User Model (PHPUnit)**
```php
// tests/unit/Models/UserModelTest.php
<?php namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\UserModel;

class UserModelTest extends CIUnitTestCase
{
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserModel();
    }

    public function testCreateUserSuccessfully()
    {
        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
        ];
        $userId = $this->model->insert($data);
        
        $this->assertIsInt($userId);
        $this->seeInDatabase('users', ['email' => 'test@example.com']);
    }

    public function testUserWithoutEmailFails()
    {
        $data = [
            'username' => 'nomail',
            'password_hash' => password_hash('secret', PASSWORD_BCRYPT),
        ];
        $result = $this->model->insert($data);
        
        $this->assertFalse($result);
    }

    public function testGetUserWithRoles()
    {
        $user = $this->model->select('users.*, GROUP_CONCAT(roles.nama) as role_names')
                             ->join('user_roles', 'user_roles.user_id = users.id')
                             ->join('roles', 'roles.id = user_roles.role_id')
                             ->groupBy('users.id')
                             ->first();
        
        $this->assertNotNull($user);
        $this->assertArrayHasKey('role_names', $user);
    }
}
```

**Test Permission Filter**
```php
// tests/unit/Filters/PermissionFilterTest.php
<?php namespace Tests\Unit\Filters;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Filters\PermissionFilter;

class PermissionFilterTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    public function testHasPermissionReturnsTrue()
    {
        $filter = new PermissionFilter();
        
        // Mock request dengan valid token
        $token = 'valid_jwt_token_here';
        $request = $this->createMockRequest(['Authorization' => "Bearer $token"]);
        
        $hasPermission = $filter->checkPermission($token, 'penilaian.create');
        $this->assertTrue($hasPermission);
    }

    public function testInvalidTokenRejectRequest()
    {
        $filter = new PermissionFilter();
        $response = $filter->before(
            $this->createMockRequest(['Authorization' => 'Bearer invalid']),
            null
        );
        
        $this->assertEqual(401, $response->getStatusCode());
    }
}
```

#### 12.3.2 Frontend Tests (Express/Node)

**Test Login Service**
```javascript
// frontend/tests/services/loginService.test.js
const axios = require('axios');
jest.mock('axios');

const loginService = require('../../src/services/loginService');

describe('Login Service', () => {
  
  test('should login successfully with valid credentials', async () => {
    const mockResponse = {
      status: 200,
      data: {
        token: 'eyJhbGc...',
        user: { id: 1, username: 'admin', email: 'admin@example.com' },
        permissions: ['penilaian.create', 'penilaian.read']
      }
    };
    
    axios.post.mockResolvedValue(mockResponse);
    
    const result = await loginService.login('admin', 'password123');
    
    expect(result.token).toBeDefined();
    expect(result.permissions).toContain('penilaian.create');
  });

  test('should reject login with invalid credentials', async () => {
    axios.post.mockRejectedValue({
      response: { status: 401, data: { message: 'Invalid credentials' } }
    });
    
    await expect(loginService.login('admin', 'wrongpass'))
      .rejects.toThrow('Invalid credentials');
  });
});
```

**Test Permission Middleware**
```javascript
// frontend/tests/middleware/permission.test.js
const permissionMiddleware = require('../../src/middleware/permission');

describe('Permission Middleware', () => {
  
  test('should allow access when user has permission', (done) => {
    const req = { session: { permissions: ['penilaian.create'] } };
    const res = {};
    const next = jest.fn();
    
    const middleware = permissionMiddleware('penilaian.create');
    middleware(req, res, next);
    
    expect(next).toHaveBeenCalled();
    done();
  });

  test('should deny access when user lacks permission', (done) => {
    const req = { session: { permissions: ['penilaian.read'] }, flash: jest.fn() };
    const res = { redirect: jest.fn() };
    
    const middleware = permissionMiddleware('penilaian.delete');
    middleware(req, res, () => {});
    
    expect(res.redirect).toHaveBeenCalledWith('/dashboard');
    done();
  });
});
```

---

### 12.4 API Testing (Postman/Thunder Client Collection)

#### Phase 1: Login & Auth Flow
```
🔹 POST http://localhost:8080/api/auth/login
Headers: Content-Type: application/json
Body:
{
  "username": "admin",
  "password": "password123"
}

Expected Response (200):
{
  "token": "eyJhbGc...",
  "user": { "id": 1, "username": "admin", "email": "admin@example.com" },
  "permissions": ["penilaian.create", "penilaian.read", "master.santri.read"]
}
```

#### Phase 2: Role & Permission CRUD
```
🔹 GET http://localhost:8080/api/roles
Headers: Authorization: Bearer {token}

🔹 POST http://localhost:8080/api/roles
Body:
{
  "nama": "Guru Tahfidz",
  "keterangan": "Role untuk pengajar tahfidz"
}

🔹 POST http://localhost:8080/api/roles/1/permissions
Body:
{
  "permission_ids": [1, 2, 5, 8]  // Assign multiple permissions
}

🔹 GET http://localhost:8080/api/permissions
```

#### Phase 3: Master Data Import
```
🔹 POST http://localhost:8080/api/santri/import
Headers: 
  - Authorization: Bearer {token}
  - Content-Type: multipart/form-data
Body (form-data):
  file: [santri_batch_20240501.csv]

CSV Format:
NIS,Nama,Kelas,Status
001/2024,Ahmad Fauzi,XII-A,Aktif
002/2024,Zainab Nur,XII-A,Aktif
```

#### Phase 4: Penilaian Input
```
🔹 POST http://localhost:8080/api/penilaian/submit
Body:
{
  "guru_id": 5,
  "periode": "2024-05",
  "penilaian": [
    {
      "santri_id": 1,
      "aspek_id": 10,
      "nilai": 85,
      "catatan": "Baik"
    },
    {
      "santri_id": 2,
      "aspek_id": 10,
      "nilai": 92,
      "catatan": "Sangat baik"
    }
  ]
}

Expected Response (201):
{
  "status": "success",
  "message": "Penilaian tersimpan",
  "total_submitted": 2,
  "periode": "2024-05"
}
```

---

### 12.5 End-to-End Testing (Manual Procedures per Phase)

#### ✅ PHASE 1 E2E Test Checklist

**Setup Awal**
- [ ] Backend running: `php spark serve --port 8080`
- [ ] Frontend running: `npm run dev` (port 3000)
- [ ] Database seeded dengan data awal
- [ ] CORS headers visible di Network tab

**Test Scenario 1: Successful Login**
1. Open http://localhost:3000/login
2. Input: username=`admin`, password=`password123`
3. Click "Login"
   - ✅ Redirect ke /dashboard (tidak ada /login)
   - ✅ Browser cookie `syiar_token` exists (DevTools → Application → Cookies)
   - ✅ httpOnly flag set (tidak visible di JS console)
   - ✅ Session memiliki user data & permissions

**Test Scenario 2: Failed Login**
1. Input: username=`admin`, password=`wrongpass`
2. Click "Login"
   - ✅ Stay di /login
   - ✅ Error message: "Username atau password salah"
   - ✅ No token cookie created

**Test Scenario 3: Expired Token**
1. Login successfully
2. Matikan backend server (Ctrl+C)
3. Refresh halaman / click menu
   - ✅ Redirect ke /login
   - ✅ Error message: "Sesi kadaluarsa, silakan login kembali"

**Test Scenario 4: Direct Access Protected Route**
1. Delete token cookie (DevTools → Application → Cookies → delete syiar_token)
2. Direct URL: http://localhost:3000/dashboard
   - ✅ Redirect ke /login

---

#### ✅ PHASE 2 E2E Test Checklist

**Setup: Create test roles & permissions**
```sql
INSERT INTO roles (nama, keterangan) VALUES ('Guru', 'Pengajar');
INSERT INTO permissions (kode, modul, aksi) VALUES 
  ('penilaian.create', 'penilaian', 'create'),
  ('penilaian.read', 'penilaian', 'read'),
  ('master.santri.read', 'master_santri', 'read');
```

**Test Scenario 1: Admin Create Role**
1. Login as super_admin
2. Navigate: Sidebar → Admin → Manajemen Role
3. Click "Tambah Role" button
4. Input: Nama=`Kepala Akademik`, Deskripsi=`Kepala bagian akademik`
5. Click "Simpan"
   - ✅ Modal tutup
   - ✅ Table refresh, role baru tampil di list
   - ✅ DB: `SELECT * FROM roles WHERE nama='Kepala Akademik'` → return 1 row

**Test Scenario 2: Assign Permissions to Role**
1. Click row role "Kepala Akademik"
2. Modal opens dengan permission list (checkboxes)
3. Check: `penilaian.read`, `penilaian.create`, `master.santri.read`
4. Click "Simpan"
   - ✅ Modal tutup
   - ✅ DB: `SELECT * FROM role_permissions WHERE role_id=X` → 3 rows

**Test Scenario 3: User dengan Multi-Role**
1. Create user: username=`guru_1`, assign 2 roles (Guru + Kepala Akademik)
2. Login as guru_1
3. Check sidebar menu
   - ✅ Menu items dari KEDUA role visible (union permission)
   - ✅ No duplicate menu items

**Test Scenario 4: Menu Visibility by Permission**
1. Login as user dengan permission `penilaian.read` ONLY (tidak ada .create)
2. Navigate ke dashboard
3. Penilaian section
   - ✅ "Lihat Rekap" button visible
   - ✅ "Input Penilaian" button HIDDEN
4. Try direct URL: /penilaian/input
   - ✅ Redirect ke /403 (Forbidden) atau /dashboard dengan error msg

---

#### ✅ PHASE 3 E2E Test Checklist

**Test Scenario 1: Import Santri via CSV**
1. Create file: `santri_import.csv`
```
NIS,Nama,Kelas,Status
2024001,Ahmad Fauzi,XII-A,Aktif
2024002,Zainab Nur,XII-A,Aktif
2024003,Muhammad Ali,XII-B,Aktif
```
2. Login as admin
3. Navigate: Master Data → Santri → Import CSV
4. Choose file → Click "Upload"
   - ✅ Progress bar shows
   - ✅ Completion message: "3 data tersimpan"
   - ✅ Table refresh, 3 rows visible

**Test Scenario 2: Santri List Pagination & Filter**
1. Import 100+ santri (bulk test)
2. Navigate: Master Data → Santri
   - ✅ Table shows 10 rows per page
   - ✅ Pagination buttons (prev/next) work
3. Filter by kelas: "XII-A"
   - ✅ Table updates, shows only XII-A santri

**Test Scenario 3: Create Kategori & Aspek Penilaian**
1. Navigate: Master Data → Kategori Penilaian
2. Click "Tambah Kategori"
3. Input: Nama=`Akhlak`, Deskripsi=`Penilaian akhlak & karakter`
4. Save → kategori ID = 5
5. Click kategori "Akhlak" → open detail
6. Click "Tambah Aspek"
7. Input: Nama=`Jujur`, Min=`0`, Max=`100`, Bobot=`20%`
8. Save
   - ✅ Aspek muncul di list
   - ✅ DB: `SELECT * FROM aspek_penilaian WHERE kategori_id=5` → 1 row

---

#### ✅ PHASE 4 E2E Test Checklist

**Test Scenario 1: Input Penilaian Single Santri**
1. Login as guru
2. Navigate: Penilaian → Input Nilai
3. Select: Kategori=`Akhlak`, Periode=`Mei 2024`
4. Form auto-load santri list
5. Select santri: "Ahmad Fauzi"
6. For each aspek (Jujur, Tawakal, dll):
   - Set nilai via slider/input: e.g., 85
   - Type catatan: "Baik"
7. Click "Simpan Draf"
   - ✅ LocalStorage saved (browser DevTools → Storage → LocalStorage)
   - ✅ Show msg: "Draf tersimpan"
8. Refresh page
   - ✅ Form state restored dari LocalStorage
9. Click "Submit"
   - ✅ POST /api/penilaian/submit hits API
   - ✅ DB: `SELECT * FROM penilaian WHERE santri_id=1 AND periode='2024-05'` → rows created

**Test Scenario 2: Batch Input + Auto-save**
1. Select kategori, periode, santri list (5 santri)
2. Input nilai untuk santri 1-3
3. Wait 30 seconds (auto-save interval)
   - ✅ LocalStorage update (inspect DevTools)
4. Navigate away, come back
   - ✅ Form restored

**Test Scenario 3: Validasi Range & Duplikat**
1. Input nilai: 105 (over max=100)
2. Click "Submit"
   - ✅ Error msg: "Nilai harus antara 0-100"
3. Correct nilai to 95
4. Input nilai untuk santri yang sama, aspek yang sama lagi
5. Click "Submit"
   - ✅ Error msg: "Penilaian untuk santri & aspek ini sudah ada"

**Test Scenario 4: Rekap & Export**
1. Navigate: Penilaian → Rekap Nilai
2. Filter: Kelas=`XII-A`, Periode=`Mei 2024`
3. Table shows semua santri + aggregated score (dengan bobot)
4. Click "Export Excel"
   - ✅ Download file `rekap_penilaian_mei_2024.xlsx`
   - ✅ Open di Excel, data format benar, formula bobot jalan

---

### 12.6 Test Data Setup Script

**Seed database dengan data testing (CI4)**
```bash
# Generate seeder
php spark make:seeder UserSeeder
php spark make:seeder RoleSeeder
php spark make:seeder PermissionSeeder
php spark make:seeder SantriSeeder

# Run all seeders
php spark db:seed --all

# Or run specific seeder
php spark db:seed RoleSeeder
```

**Example RoleSeeder (app/Database/Seeds/RoleSeeder.php)**
```php
<?php namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['nama' => 'Super Admin', 'keterangan' => 'Full access'],
            ['nama' => 'Admin Akademik', 'keterangan' => 'Manage academic data'],
            ['nama' => 'Guru', 'keterangan' => 'Teacher/Asatidz'],
            ['nama' => 'Wali', 'keterangan' => 'Parent/Guardian'],
        ];
        $this->db->table('roles')->insertBatch($data);
    }
}
```

---

### 12.7 Automated Testing - Run Command

```bash
# Backend: Run all unit tests
cd backend
php spark test tests/unit

# Run specific test class
php spark test tests/unit/Models/UserModelTest

# With coverage report
php spark test --coverage --coverage-report html

# Frontend: Run all tests
cd frontend
npm test

# Run with coverage
npm test -- --coverage

# Run specific test file
npm test -- loginService.test.js
```

---

### 12.8 Testing Checklist Template (Copy untuk setiap phase)

```
## PHASE X Testing Checklist - [DATE]

### Unit Tests
- [ ] Backend unit tests passing: `php spark test tests/unit --filter Phase[X]`
- [ ] Frontend unit tests passing: `npm test -- --testNamePattern="Phase[X]"`
- [ ] Code coverage >80%

### API Tests (Postman)
- [ ] All endpoints return expected status codes
- [ ] Request/response formats validated
- [ ] Authentication headers working
- [ ] Error scenarios handled (400, 401, 403, 404, 500)

### E2E Tests (Manual)
- [ ] Test Scenario 1: [description] ✅ / ❌
- [ ] Test Scenario 2: [description] ✅ / ❌
- [ ] Test Scenario 3: [description] ✅ / ❌
- [ ] Test Scenario N: [description] ✅ / ❌

### Data Integrity
- [ ] Database constraints working (FK, unique, not null)
- [ ] Soft deletes behaving correctly
- [ ] Timestamps (created_at, updated_at) automatic

### Performance
- [ ] Page load time <2s
- [ ] Large dataset queries (100+ rows) perform <500ms
- [ ] No N+1 query issues

### Security
- [ ] XSS prevention (input sanitization)
- [ ] CSRF token working
- [ ] Rate limiting on auth endpoints
- [ ] No sensitive data in logs
- [ ] SQL injection tests passed

### Browser Compatibility
- [ ] Chrome ✅
- [ ] Firefox ✅
- [ ] Safari ✅
- [ ] Edge ✅

### Bugs Found
1. [BUG-001] - Description - Priority: [HIGH/MED/LOW] - Assigned to: [name]
2. [BUG-002] - ...

### Sign-off
- QA: _______________ Date: ___________
- PM: _______________ Date: ___________
```

---

### 12.9 Common Issues & Troubleshooting

| Issue | Symptoms | Solution |
|-------|----------|----------|
| **CORS Error** | `Access to XMLHttpRequest blocked` | Check CI4 `Config/CORS.php`, ensure `allowedOrigins: ['http://localhost:3000']`, restart server |
| **Token Expired** | `401 Unauthorized` on API call | Implement refresh token endpoint, extend token TTL, check clock sync between servers |
| **Login Infinite Loop** | Redirect /login → /login repeatedly | Check AuthFilter logic, ensure token validated correctly, trace middleware order |
| **Permission Denied False Positive** | User has permission but still blocked | Cache stale - clear Redis/session, verify `role_permissions` table populated, check union logic |
| **CSV Import Fails** | "0 rows imported", silent fail | Check CSV encoding (UTF-8), delimiter (comma), column order matches, add logging in API |
| **Penilaian Form Not Saving** | Form submits but no DB record | Check API endpoint returns 201 (not 200), validate body format matches schema, check request headers |
| **Auto-save Not Working** | Refresh page, data lost | Check LocalStorage enabled (DevTools → Application), verify 30s interval set, test on private/incognito mode |
| **Export Excel Corrupted** | File opens but shows error | Verify XLSX library installed (PhpSpreadsheet), check file permissions on `/writable/`, test with small dataset first |

---
## �📎 Lampiran & Fase Lanjutan
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

