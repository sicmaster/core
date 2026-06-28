# CLAUDE.md — AI Agent Operating Manual

> อ่านไฟล์นี้ก่อนทำงานทุกครั้ง นี่คือกฎสูงสุดของ repo นี้
> โมเดลการทำงาน: **AI พัฒนา / มนุษย์ review ก่อน deploy** เอกสารนี้จึงต้องชัดพอให้ AI ทำต่อได้โดยไม่เดา

---

## 1. โปรเจกต์นี้คืออะไร

**Core App** — โครงตั้งต้น (starter/foundation) แบบ reusable สำหรับงาน freelance รับทำเว็บหน้าบ้าน-หลังบ้าน
เป้าหมาย: copy ไปเริ่มงานลูกค้าใหม่ได้เร็ว ไม่ต้องเริ่มจาก 0 ทุกครั้ง → ลดเวลา dev ต่องาน → margin ดีขึ้น

**ลักษณะงานที่ core นี้รองรับ:**
- เว็บบริษัท / landing / ร้านค้า / ระบบจัดการข้อมูล (หน้าบ้านแสดงผล + หลังบ้านจัดการ)
- **1 ลูกค้า = 1 deploy = 1 database** (single-tenant ไม่ใช่ SaaS)
- ลูกค้า deploy เองบน shared hosting/cPanel หรือ VPS

> นี่ไม่ใช่ SaaS — ไม่มี multi-tenant, ไม่มี RLS, ไม่มี tenant isolation
> Core ใส่เฉพาะของที่ **ใช้ทุกงานจริง** ของที่ "อาจได้ใช้" ไม่ใส่ รอจนเจองานจริงค่อยเพิ่ม

---

## 2. โมเดลการทำงาน AI ↔ มนุษย์

- **AI (คุณ)**: เขียนโค้ด, migration, test, เอกสาร, เปิด PR
- **มนุษย์**: review + approve โดยเฉพาะ **ก่อน deploy** เป็น checkpoint หลัก
- ระหว่างทาง AI เดินงานเองได้ ติดปัญหาตามกฎ STOP (ดูข้อ 7) → รวบรวมมาถาม
- ทุก output ต้อง **อธิบายเหตุผลได้** และ **มี test พิสูจน์** ก่อนขอ review
- ไม่แน่ใจ → **ถาม ไม่ใช่เดา**

---

## 3. INVARIANTS — กฎที่ห้ามละเมิดเด็ดขาด

ห้ามแก้/ลด/อ้อมกฎเหล่านี้เพื่อให้ test ผ่านหรือให้ feature ง่ายขึ้น ถ้ากฎขวางงาน → หยุดแล้วถาม

1. **Deploy ได้ทุกที่ (cPanel-safe)** — core ต้อง deploy บน shared hosting/cPanel ได้
   - **ห้ามพึ่ง service ที่ต้องรัน process ค้างตลอด** (Redis, websocket server, queue worker แบบ daemon) เว้นแต่ทำเป็น optional ที่ปิดได้
   - queue/cache/session ใช้ driver `database` หรือ `file` เป็น default (ไม่ใช่ Redis)
   - งานที่ต้อง worker/realtime จริง = feature เสริมเฉพาะลูกค้าที่มี VPS เท่านั้น ไม่ใช่ core
2. **DB-agnostic** — เขียนผ่าน Eloquent/Query Builder ใช้เฉพาะ feature ที่ **ทั้ง MySQL และ MariaDB** รองรับเหมือนกัน
   - **ห้าม** ผูกกับ feature เฉพาะ DB (JSON function ลึก, generated column เฉพาะตัว, PostgreSQL-only)
   - default = MySQL/MariaDB; สลับ DB ได้ด้วยการแก้ `.env` บรรทัดเดียว ไม่แตะโค้ด
3. **Single-tenant** — 1 deploy = 1 ลูกค้า = 1 database
   - **ไม่มี** `tenant_id`, ไม่มี RLS, ไม่มี tenant scoping — ถ้าเผลอใส่ = ผิด ลบออก
   - การกันสิทธิ์ใช้ **RBAC/permission** (ใครทำอะไรได้) ไม่ใช่ tenant isolation
4. **แยกหน้าบ้าน/หลังบ้านชัด**
   - **หน้าบ้าน** (`/`...) = **Blade** server-render เพื่อ SEO + เร็ว + deploy ง่าย (ไม่พึ่ง Node ตอน serve)
   - **หลังบ้าน** (`/admin`...) = **Inertia + React** อยู่หลัง auth
   - root view แยกตาม route (`HandleInertiaRequests::rootView`) — admin ใช้ React, หน้าบ้านไม่ผ่าน Inertia
5. **เงินที่ถูกต้อง (ถ้างานมีเรื่องเงิน)** — เก็บเป็น **integer หน่วยย่อยสุด (สตางค์)** ไม่ใช่ float/decimal
   - percent = basis points; เศษมีได้แค่ในสูตร แล้วปัดก่อน return
   - ไม่ใช่ทุกงานมีเรื่องเงิน — ใส่เมื่อจำเป็น
6. **Audit ที่จำเป็น** — action สำคัญ (สร้าง/แก้/ลบข้อมูลหลัก, login, เปลี่ยน permission) ควร log ใครทำเมื่อไหร่
   - ใช้ Laravel ปกติ (model events / observer / activity log package) ไม่ต้องเขียน DB trigger เอง
7. **Soft delete สำหรับ master data** — ข้อมูลหลักลบแบบ soft (`deleted_at`) ไม่ hard delete; ข้อมูลชั่วคราว hard delete ได้
8. **Validation ที่ขอบระบบ** — ทุก input จาก request ต้อง validate (Form Request) ก่อนเข้า logic; error คืนเป็นรูปแบบเดียวกันทั้งระบบ
9. **Configuration > custom code** — ความต้องการที่ต่างกันต่อลูกค้า แก้ด้วย settings/config ไม่ hard-code รายเจ้าใน core
10. **Core เล็กเข้าไว้ (YAGNI)** — ใส่เฉพาะของที่ใช้ทุกงาน; ของเฉพาะงาน = อยู่ในโปรเจกต์ลูกค้า ไม่ดันกลับเข้า core จนกว่าจะพิสูจน์ว่าซ้ำจริง

> ทำไมกฎพวกนี้ถึงมีอยู่ → อ่าน `docs/architecture/decisions/` (ADR) ห้าม override ADR โดยไม่เปิด ADR ใหม่ที่มนุษย์ approve

---

## 4. Tech Stack & Conventions

- **Framework:** Laravel 13 (PHP) — official React starter kit
- **หน้าบ้าน:** Blade + Tailwind (server-render, SEO)
- **หลังบ้าน:** Inertia + React 19 + TypeScript + Tailwind + shadcn/ui (จาก Laravel React starter kit)
- **DB:** MySQL/MariaDB (default) ผ่าน Eloquent — DB-agnostic (ดู invariant 2)
- **Auth:** Laravel Fortify (built-in จาก starter kit: login/register/reset/email verification) + RBAC ผ่าน package มาตรฐาน (เช่น spatie/laravel-permission)
- **Dev env:** Laravel Sail (Docker) — env ตรงกันทุกโปรเจกต์
- **Deploy:** PHP hosting ปกติ (cPanel/VPS) — ชี้ root ไป `/public`, ตั้ง `.env`, รัน `migrate`
- **Migration:** Laravel migration (forward-only ในทางปฏิบัติ — ไม่แก้ migration ที่ลูกค้า deploy แล้ว สร้างไฟล์ใหม่)
- **Conventions:** snake_case (DB), identifier เป็นภาษาอังกฤษ, PSR-12 (PHP), ESLint/Prettier (TS/React)
- เขียน test คู่กับการเปลี่ยน logic; logic สำคัญต้องมี test

---

## 5. โครงโปรเจกต์ (เป้าหมาย v0.1)

```
core/
├── app/Http/Controllers/
│   ├── Frontend/        controller หน้าบ้าน (Blade)
│   └── Admin/           controller หลังบ้าน (Inertia)
├── routes/
│   ├── web.php          หน้าบ้าน (domain.com/...)
│   └── admin.php        หลังบ้าน (domain.com/admin/...)
├── resources/
│   ├── views/           Blade หน้าบ้าน
│   └── js/pages/admin/  React หลังบ้าน
├── database/migrations/
└── docs/                เอกสาร (ไม่ใช่ไฟล์ที่ Claude Code รัน)
```

**ของที่ core v0.1 ควรมี** (ของที่ใช้ทุกงาน): auth, RBAC/permission, CRUD admin pattern, table+form component, media/file upload, settings, audit log, layout admin + หน้าบ้าน baseline (SEO meta)

---

## 6. Workflow ของ AI ต่อ 1 task

1. อ่าน `README.md` → ไฟล์ที่เกี่ยวข้องใน `docs/` → ADR ที่เกี่ยวข้อง
2. ยืนยัน task อยู่ใน scope ปัจจุบัน (ดู `docs/delivery/`) ถ้าไม่ → ถาม
3. เขียนโค้ด + migration (ถ้ามี) + test
4. รัน test ทั้งหมด
5. เปิด PR พร้อมอธิบาย: ทำอะไร, แตะ invariant ไหนไหม, test อะไรพิสูจน์, decision ใหม่มีไหม (ถ้ามีต้องมี ADR)
6. รอมนุษย์ review/approve ก่อน deploy — **ห้าม merge/deploy เอง**

---

## 7. STOP-vs-LOG (ระหว่างเดินงานเอง)

**STOP ทันที** (รวบรวมเขียน `OPEN-QUESTIONS.md` แล้วหยุด รอมนุษย์):
- งานขัด invariant และทางเดียวที่ไปต่อคือลด strictness
- ต้องเปิด ADR ใหม่ / เปลี่ยน decision สถาปัตยกรรม
- ออกนอก scope ที่ตกลงไว้
- จะใส่ของที่ขัด "cPanel-safe" หรือ "DB-agnostic" หรือ "single-tenant"

**LOG แล้วเดินต่อ** (เขียน `DECISIONS-LOG.md`):
- naming, edge case เล็ก, เลือก package/วิธี implement, refactor เล็ก

---

## 8. Human Review Gates (มนุษย์เช็คก่อน approve)

- [ ] deploy ได้บน cPanel (ไม่พึ่ง Redis/worker ค้าง โดยไม่จำเป็น)
- [ ] ไม่มี feature เฉพาะ DB ที่ผูกกับ MySQL หรือ MariaDB ตัวเดียว
- [ ] ไม่มี `tenant_id`/RLS/tenant scoping หลุดเข้ามา (นี่คือ single-tenant)
- [ ] input มี validation; master data เป็น soft delete
- [ ] การเปลี่ยนที่แตะ auth/permission/เงิน มี test
- [ ] ถ้ามี decision เชิงสถาปัตยกรรมใหม่ → มี ADR แนบ
- [ ] core ไม่บวมด้วยของเฉพาะงาน (YAGNI)

---

## 9. เมื่อไม่แน่ใจ

- ขัดกับ invariant → **หยุด ถาม** อย่าทำต่อ
- ความต้องการกำกวม → ถามก่อนเขียน
- เจอ trade-off → เสนอทางเลือกพร้อมข้อดีข้อเสีย ให้มนุษย์เลือก
- **ห้ามลด strictness ของกฎเพื่อให้ test ผ่าน** — test ที่ fail เพราะ invariant = โค้ดผิด ไม่ใช่กฎผิด

---

## 10. แผนที่เอกสาร

| ไฟล์ | คืออะไร |
|---|---|
| `README.md` | จุดเริ่มต้น + ลำดับการอ่าน |
| `docs/architecture/` | สถาปัตยกรรม core + ADR |
| `docs/delivery/` | scaffold-plan + task list + DoD |
| `docs/ai/` | คำอธิบายทีม agent + pipeline (เอกสาร ไม่ใช่ไฟล์รัน) |
| `docs/logs/` | log ของ pipeline: `WORKLOG.md`, `OPEN-QUESTIONS.md`, `DECISIONS-LOG.md` |
| `.claude/agents/` · `.claude/commands/` | ไฟล์ที่ Claude Code รันจริง (อยู่ที่บังคับ ห้ามย้าย) |

> **ตำแหน่งไฟล์ log:** WORKLOG / OPEN-QUESTIONS / DECISIONS-LOG อยู่ที่ `docs/logs/` (ไม่ใช่ root) — agent ที่ต้องเขียน log ให้เขียนที่ `docs/logs/<ชื่อไฟล์>`
