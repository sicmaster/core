# Scaffold Plan: Core App v0.0.1

> Task breakdown สำหรับขึ้นโครง Core App เวอร์ชัน 0.0.1
> อ่าน `CLAUDE.md` + `docs/architecture/architecture.md` ก่อนทำงาน

---

## Scope v0.0.1 (ทำแค่นี้ ห้ามเกิน)

- [x] Laravel new + React starter kit (Inertia+React 19+TS+shadcn) + Laravel Sail + MySQL/MariaDB
- [x] Auth เข้า /admin ได้ (login/logout จาก starter kit)
- [x] Layout admin: sidebar + topbar + dashboard เปล่า
- [x] RBAC ขั้นต่ำ: spatie/laravel-permission + middleware กัน route /admin
- [x] CRUD User Management (list+filter+create+edit+soft delete) เป็น pattern ต้นแบบ

**ยังไม่ทำ:** media upload, settings, audit log, หน้าบ้าน Blade

---

## Task List

### Task 1: Laravel Installation + Starter Kit

| Field | Value |
|-------|-------|
| **Dependency** | - (task แรก) |
| **Description** | สร้างโปรเจกต์ Laravel 12 ใหม่ด้วย React starter kit (Inertia + React 19 + TypeScript + Tailwind + shadcn/ui) |
| **DoD** | 1. รัน `laravel new core` แล้วเลือก **React** starter kit (official) ตอน interactive prompt — **ห้ามใช้ Breeze/Jetstream** (ถูก deprecated ออกจาก Laravel 12 installer แล้ว)<br/>2. ถ้าต้อง non-interactive: ใช้ `laravel new core --react` (ดู `laravel new --help` ยืนยัน flag กับเวอร์ชัน installer ที่ติดตั้ง) เลือก auth = Laravel built-in, TypeScript = yes<br/>3. ยืนยันว่าได้ official React kit จริง: มี `resources/js/pages/` + `resources/js/layouts/` (ตัวพิมพ์เล็ก), มี shadcn/ui ใน `resources/js/components/ui/`, Tailwind v4, Inertia 2 — **ถ้าเห็น `ProfileController`, `routes/auth.php`, หรือ `Pages/` ตัวใหญ่ = นั่นคือ Breeze ผิด ให้หยุดและทำใหม่**<br/>4. รัน `npm install && npm run build` ได้ไม่ error |
| **Invariants** | **แยกหน้าบ้าน/หลังบ้าน (#4):** ต้องเป็น official React kit (มี shadcn ตาม CLAUDE.md tech stack) ไม่ใช่ Breeze |

---

### Task 2: Laravel Sail Setup

| Field | Value |
|-------|-------|
| **Dependency** | Task 1 |
| **Description** | ติดตั้ง Laravel Sail (Docker) + ตั้งค่า MySQL/MariaDB เป็น default database |
| **DoD** | 1. `sail up` รัน container ได้<br/>2. `.env` ชี้ไป database ใน Sail container<br/>3. รัน `sail artisan migrate` สำเร็จ<br/>4. เปิด browser เข้า `localhost` เห็น Laravel welcome หรือ starter kit home |
| **Invariants** | **cPanel-safe (#1):** ตั้ง `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database` ใน `.env.example` เป็น default (ไม่ใช่ Redis) |

---

### Task 3: Auth Configuration (Starter Kit)

| Field | Value |
|-------|-------|
| **Dependency** | Task 2 |
| **Description** | ยืนยันว่า auth จาก starter kit ทำงานปกติ (login, logout, register) และเตรียม route `/admin` ให้อยู่หลัง auth + วางโครงโฟลเดอร์ตาม architecture |
| **DoD** | 1. เข้า `/login` ได้ เห็น form<br/>2. register user ใหม่ได้<br/>3. login เข้าระบบ -> redirect ไป dashboard<br/>4. logout กลับหน้า login ได้<br/>5. สร้าง `routes/admin.php` แยก route หลังบ้าน + register ใน `RouteServiceProvider` หรือ `bootstrap/app.php`<br/>6. สร้างโฟลเดอร์ `app/Http/Controllers/Frontend/` (ว่างไปก่อน หรือใส่ `.gitkeep`)<br/>7. สร้างโฟลเดอร์ `app/Http/Controllers/Admin/` สำหรับ controller หลังบ้าน<br/>8. ใส่ `rootView()` logic ใน `HandleInertiaRequests` middleware ที่ check route name 'admin.*' เพื่อเตรียมแยก root view หน้าบ้าน/หลังบ้าน (invariant #4) |
| **Invariants** | **แยกหน้าบ้าน/หลังบ้าน (#4):** เตรียม rootView logic ไว้ตั้งแต่ต้น แม้ยังใช้ `app.blade.php` เดียว<br/>**cPanel-safe (#1) — case-sensitivity:** ชื่อโฟลเดอร์/ไฟล์ใน `resources/js/` ใช้ case ให้ตรงกับ official kit เสมอ (`pages/`, `layouts/`, `components/` ตัวเล็ก) — **ห้ามปน case** เพราะ Linux (cPanel/VPS) case-sensitive → import ผิด case จะ build/รันพังตอน deploy ถึงแม้ Mac จะรันผ่านก็ตาม |

---

### Task 4: Admin Layout (Sidebar + Topbar + Dashboard)

| Field | Value |
|-------|-------|
| **Dependency** | Task 3 |
| **Description** | สร้าง layout หลังบ้าน มี sidebar (เมนูนำทาง) + topbar (user info, logout) + หน้า dashboard เปล่า |
| **DoD** | 1. มี React component `AdminLayout.tsx` (หรือชื่อคล้าย) ที่ใช้ครอบ page admin<br/>2. sidebar แสดงเมนู Dashboard, Users (placeholder)<br/>3. topbar แสดงชื่อ user ที่ login + ปุ่ม logout<br/>4. หน้า `/admin/dashboard` render เปล่า แสดงข้อความ "Dashboard" |
| **Invariants** | ไม่แตะ |

---

### Task 5: RBAC Setup (spatie/laravel-permission)

| Field | Value |
|-------|-------|
| **Dependency** | Task 3 |
| **Description** | ติดตั้ง spatie/laravel-permission + สร้าง migration + seed role/permission ขั้นต่ำ (2 roles: admin, staff) |
| **DoD** | 1. รัน `composer require spatie/laravel-permission`<br/>2. publish + migrate tables (roles, permissions, model_has_roles, etc.)<br/>3. seeder สร้าง role `admin` + role `staff` + permission `access admin`<br/>4. User model ใช้ `HasRoles` trait<br/>5. รัน seeder ได้ไม่ error |
| **Invariants** | **DB-agnostic (#2):** migration ต้องใช้ standard column types (ไม่ผูก feature เฉพาะ MySQL)<br/>**Single-tenant (#3):** ไม่มี `tenant_id` ใน tables ของ spatie |

---

### Task 6: Admin Middleware (Permission Check)

| Field | Value |
|-------|-------|
| **Dependency** | Task 5 |
| **Description** | สร้าง middleware กัน route `/admin/*` ให้เฉพาะ user ที่มี permission/role ที่กำหนดเข้าได้ |
| **DoD** | 1. มี middleware (อาจใช้ของ spatie หรือสร้างเอง) ที่เช็ค role/permission<br/>2. route group `/admin` ใช้ middleware นี้<br/>3. user ไม่มี permission เข้า `/admin/dashboard` -> redirect หรือ 403<br/>4. user มี permission (role admin หรือ staff) เข้าได้ปกติ<br/>5. มี test พิสูจน์ทั้ง 2 กรณี |
| **Invariants** | ไม่แตะ |

---

### Task 7: User Model Enhancement (Soft Delete)

| Field | Value |
|-------|-------|
| **Dependency** | Task 5 |
| **Description** | เพิ่ม soft delete ให้ User model + migration เพิ่ม `deleted_at` (ถ้ายังไม่มี) |
| **DoD** | 1. User model ใช้ `SoftDeletes` trait<br/>2. ตาราง `users` มี column `deleted_at`<br/>3. ลบ user -> record ไม่หายจาก DB (มี deleted_at)<br/>4. query ปกติไม่เห็น user ที่ถูก soft delete |
| **Invariants** | **Soft delete (#7):** User เป็น master data ต้อง soft delete |

---

### Task 8: User CRUD - List + Filter

| Field | Value |
|-------|-------|
| **Dependency** | Task 6, Task 7 |
| **Description** | หน้า list users + filter (search, pagination) เป็น pattern ต้นแบบ |
| **DoD** | 1. Controller `Admin/UserController@index`<br/>2. React page `admin/users/Index.tsx` แสดง table<br/>3. รองรับ search (ชื่อ, email) + pagination<br/>4. แสดง role ของแต่ละ user<br/>5. ไม่แสดง user ที่ถูก soft delete (ปกติ)<br/>6. **มี Feature Test ครอบ:** test list users, test search ทำงานถูกต้อง |
| **Invariants** | **DB-agnostic (#2):** query ใช้ Eloquent/Query Builder มาตรฐาน (LIKE, whereRaw กับ format ที่ทั้ง MySQL/MariaDB รองรับ) |

---

### Task 9: User CRUD - Create

| Field | Value |
|-------|-------|
| **Dependency** | Task 8 |
| **Description** | หน้า + logic สร้าง user ใหม่ พร้อม validation |
| **DoD** | 1. Controller `Admin/UserController@create`, `@store`<br/>2. React page `admin/users/Create.tsx` มี form (name, email, password, role dropdown)<br/>3. form มี dropdown เลือก role (admin หรือ staff)<br/>4. Form Request validate input (required, email unique, password min, role exists)<br/>5. สร้าง user + assign role ที่เลือก -> redirect กลับ list<br/>6. validation error แสดงใน form<br/>7. **มี Feature Test ครอบ:** test create user สำเร็จ, test validation errors, test role assignment |
| **Invariants** | **Validation (#8):** ใช้ Form Request ไม่ validate ใน controller |

---

### Task 10: User CRUD - Edit/Update

| Field | Value |
|-------|-------|
| **Dependency** | Task 9 |
| **Description** | หน้า + logic แก้ไข user |
| **DoD** | 1. Controller `Admin/UserController@edit`, `@update`<br/>2. React page `admin/users/Edit.tsx` มี form pre-filled + role dropdown<br/>3. Form Request validate (email unique except self, password optional)<br/>4. แก้ไข user + sync role สำเร็จ -> redirect กลับ list<br/>5. validation error แสดงใน form<br/>6. **มี Feature Test ครอบ:** test edit user สำเร็จ, test validation errors, test role sync |
| **Invariants** | **Validation (#8):** ใช้ Form Request |

---

### Task 11: User CRUD - Soft Delete

| Field | Value |
|-------|-------|
| **Dependency** | Task 10 |
| **Description** | ปุ่มลบ user (soft delete) + confirm dialog |
| **DoD** | 1. Controller `Admin/UserController@destroy`<br/>2. ปุ่ม delete ในหน้า list มี confirm dialog<br/>3. กดลบ -> soft delete (deleted_at = now)<br/>4. user หายจาก list (แต่ยังอยู่ใน DB)<br/>5. ป้องกันลบตัวเอง (user ที่ login อยู่)<br/>6. **มี Feature Test ครอบ:** test soft delete ทำงาน, test ลบตัวเองไม่ได้ |
| **Invariants** | **Soft delete (#7):** ใช้ `$user->delete()` ไม่ใช่ `forceDelete()` |

---

### Task 12: Test Suite Review + Edge Cases

| Field | Value |
|-------|-------|
| **Dependency** | Task 11 |
| **Description** | รวบ test ที่เขียนมาทั้งหมด + เติม edge case ที่ขาด + รัน full suite |
| **DoD** | 1. Review test coverage จาก Task 6, 8, 9, 10, 11<br/>2. เติม edge case ที่ขาด (เช่น: concurrent edit, invalid role assignment)<br/>3. Test user ไม่มี permission เข้า /admin ไม่ได้<br/>4. Test user มี permission เข้าได้<br/>5. รัน `sail artisan test` ผ่านทั้งหมด<br/>6. No failing tests, no skipped tests |
| **Invariants** | ไม่แตะ |

---

### Task 13: Documentation + Cleanup

| Field | Value |
|-------|-------|
| **Dependency** | Task 12 |
| **Description** | เขียน/ปรับ README + cleanup code + prepare for review |
| **DoD** | 1. README.md อัปเดต (วิธี setup, run, test)<br/>2. `.env.example` มี config ครบ<br/>3. ลบ code/file ที่ไม่ใช้<br/>4. ESLint/Prettier pass<br/>5. `sail artisan test` pass<br/>6. `npm run build` pass |
| **Invariants** | ไม่แตะ |
| **Status** | ✅ DONE |

---

## Dependency Graph

```
Task 1 (Laravel Install)
    |
    v
Task 2 (Sail Setup)
    |
    v
Task 3 (Auth Config + Folder Structure + rootView Logic)
    |
    +------------------+
    |                  |
    v                  v
Task 4 (Layout)    Task 5 (RBAC: admin+staff roles)
                       |
          +------------+------------+
          |                         |
          v                         v
      Task 6 (Middleware)       Task 7 (Soft Delete)
          |                         |
          +------------+------------+
                       |
                       v
                   Task 8 (List + Tests)
                       |
                       v
                   Task 9 (Create + Tests)
                       |
                       v
                   Task 10 (Edit + Tests)
                       |
                       v
                   Task 11 (Delete + Tests)
                       |
                       v
                   Task 12 (Test Review + Edge Cases)
                       |
                       v
                   Task 13 (Docs)
```

---

## Invariant Summary for v0.0.1

| Invariant | Task ที่แตะ | Action |
|-----------|------------|--------|
| #1 cPanel-safe | Task 2 | ตั้ง queue/cache/session เป็น database driver |
| #2 DB-agnostic | Task 5, 8 | ใช้ Eloquent มาตรฐาน ไม่ผูก feature เฉพาะ DB |
| #3 Single-tenant | Task 5 | ไม่มี tenant_id ใน tables |
| #4 แยกหน้าบ้าน/หลังบ้าน | Task 3 | เตรียม rootView() logic + folder structure ตั้งแต่แรก |
| #7 Soft delete | Task 7, 11 | User ใช้ SoftDeletes trait |
| #8 Validation | Task 9, 10 | ทุก input ผ่าน Form Request |

---

## RESOLVED (คำถามที่ตัดสินแล้ว)

> คำถามที่มนุษย์ตัดสินใจแล้ว

### R1: Root view naming convention

**Context:** architecture.md ระบุว่า admin ใช้ root view แยก (`admin.blade.php`) แต่ React starter kit อาจใช้ `app.blade.php` เดียว

**Decision:** ใช้ `app.blade.php` เดียว แต่ใส่ `rootView()` logic ใน `HandleInertiaRequests` middleware ที่ check route name 'admin.*' ไว้ตั้งแต่ v0.0.1 เพื่อเตรียมแยกหน้าบ้าน (เป็น invariant #4) -- Logic นี้อยู่ใน Task 3

---

### R2: Default roles/permissions structure

**Context:** ต้อง seed role/permission ขั้นต่ำ แต่ยังไม่มี ADR กำหนดโครงสร้าง

**Decision:** ใช้ 2 roles: `admin` และ `staff` + permission `access admin` -- form create user จะมี dropdown ให้เลือก role ได้จริง

---

### R3: ใช้ shadcn/ui components ตาม starter kit หรือ customize

**Context:** Laravel React starter kit มาพร้อม shadcn/ui แต่อาจไม่ครบทุก component ที่ต้องใช้ (เช่น DataTable, Dialog)

**Decision:** เพิ่ม shadcn component เมื่อจำเป็นผ่าน `npx shadcn@latest add` (YAGNI)

---

## Estimation Summary

| Task | Est. Hours |
|------|-----------|
| Task 1-2 (Setup) | 1-2 |
| Task 3 (Auth + Folder + rootView) | 1 |
| Task 4 (Layout) | 2-3 |
| Task 5-6 (RBAC) | 2 |
| Task 7 (Soft Delete) | 0.5 |
| Task 8-11 (CRUD + Tests per task) | 6-8 |
| Task 12 (Test Review) | 1-2 |
| Task 13 (Docs) | 1 |
| **Total** | **14-19 hours** |

---

## Approval Checklist (for human review)

- [ ] Task list ครบตาม scope v0.0.1
- [ ] ไม่มี task ที่เกิน scope (media, settings, audit log, หน้าบ้าน)
- [ ] Invariants ที่แตะถูกระบุครบ
- [ ] RESOLVED section มีคำตอบครบทุกคำถามเดิม
- [ ] Dependency graph ถูกต้อง

---

*Last updated: 2026-06-28*
*Status: ✅ v0.0.1 COMPLETE — ทุก task (1-13) เสร็จ + test 95 passed, 4 skipped (2FA ปิด), 0 failed*
