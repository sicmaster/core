# WORKLOG

> บันทึกความคืบหน้าของ pipeline `/feature` แบบต่อเนื่อง (subagent จำข้ามกันไม่ได้ จึงเขียนที่นี่)
> append เท่านั้น — entry ใหม่ไว้บนสุด

## v0.0.1 — Core App Scaffold (เสร็จ 2026-06-28)

โครง Core App (Laravel 13 + Inertia React + Sail/MariaDB) พร้อม User Management CRUD เป็น pattern ต้นแบบ
**ผล test รวม: 95 passed, 4 skipped (2FA ปิด), 0 failed**

| Task | สิ่งที่ทำ | สถานะ |
|---|---|---|
| 1-2 | Laravel 13 + official React starter kit (Inertia 2/React 19/TS/shadcn/Wayfinder/Fortify) + Sail + MariaDB; cPanel-safe drivers (database queue/cache/session) | ✅ |
| 3 | แยก route admin (`/admin`) + `rootView()` logic (admin.* ) + folder Controllers/Admin + Frontend | ✅ |
| 4 | Admin layout — reuse AppLayout ของ kit + context-aware sidebar (admin menu) | ✅ |
| 5 | RBAC (spatie/laravel-permission) — role admin+staff, permission "access admin", seeder idempotent | ✅ |
| 6 | Middleware `permission:access admin` กัน /admin | ✅ |
| 7 | Soft delete User (migration ใหม่ + SoftDeletes trait) | ✅ |
| 8 | User CRUD List — search (DB-agnostic LIKE) + pagination + eager load roles | ✅ |
| 9 | User CRUD Create — Form Request + role assign + password hash (no double-hash) | ✅ |
| 10 | User CRUD Edit — email unique except self, password optional, syncRoles | ✅ |
| 11 | User CRUD Delete — soft delete + confirm dialog + กันลบตัวเอง (backend+frontend) | ✅ |
| 12 | รวบ test + เติม edge case (staff CRUD, pagination, empty search) + ลบ ExampleTest | ✅ |
| 13 | Docs — README (developer onboarding), อัปเดต scaffold-plan, WORKLOG นี้ | ✅ |

**Architecture decisions ระหว่างทาง:**
- ADR-0001: แยก Frontend/Admin (Model ใช้ร่วม + scope, Controller แยก folder, View แยก, Asset แยก Vite entry ที่ v0.1)

**ยังไม่ทำ (v0.1+):** media upload, settings (custom), audit log, หน้าบ้าน Blade จริง + asset separation, REST API (Sanctum)

**Workflow ที่ใช้:** Claude วาง task (scaffold-plan) + review โค้ด · Gemini implement ตาม task · มนุษย์ commit · ทุก tool อ่านกฎเดียวกันผ่าน AGENTS.md (symlink → CLAUDE.md)

---

| วันที่ | feature | stage | agent | สรุปสิ่งที่ทำ | สถานะ |
|---|---|---|---|---|---|
| 2026-06-28 | v0.0.1 scaffold | ทั้งหมด | Claude(plan/review)+Gemini(dev) | Task 1-13 ครบ | ✅ DONE |
