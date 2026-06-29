# ADR-0003 — Permission Model: Resource-based RBAC (`resource.action`)

- **สถานะ:** Accepted
- **วันที่:** 2026-06-28
- **เกี่ยวข้อง:** ADR-0002 (admin auth zone), CLAUDE.md invariant #3 (single-tenant RBAC)

---

## ปัญหา

v0.0.1 มี permission เดียว: `access admin` (เข้าหลังบ้านได้/ไม่ได้) — หยาบเกินไป
ต้องการ: admin (ลูกค้า) จัดการได้เองว่า **role ไหนทำ action อะไรได้กับ resource ไหน**
เช่น role "บรรณาธิการ" → จัดการ news ได้ แต่ลบ user ไม่ได้

## การตัดสินใจ

### 1. Permission เป็นรูปแบบ `resource.action`
- action มาตรฐาน = **CRUD**: `create`, `read`, `update`, `delete`
- ตัวอย่าง: `users.create`, `users.read`, `news.update`, `roles.delete`
- ทุก resource มี 4 permission (CRUD) เสมอ — สม่ำเสมอ คาดเดาได้

### 2. Resource มาจากโค้ด (migrate) เท่านั้น — **ไม่สร้างผ่าน UI**
- resource ใหม่ (เช่น news) เพิ่มตอน dev ทำฟีเจอร์ ผ่าน **migration/seeder** + มี controller/หน้าจริงรองรับ
- **เหตุผล:** การันตี "มี permission = มีโค้ดจริงรองรับ" ไม่มี permission ลอย/เมนูผีที่กดไปเจอ 404
- รายการ resource เก็บเป็น config กลาง (เช่น `config/permissions.php` หรือ enum) ที่ seeder อ่านไป generate CRUD permission

### 3. Admin จัดการผ่าน UI ได้ (ภายในขอบเขต)
- **สร้าง/แก้/ลบ Role** เอง (เช่น "บรรณาธิการ", "ผู้จัดการ")
- **ติ๊ก permission matrix** (resource × CRUD) ว่า role ไหนทำอะไรได้
- assign role ให้ user (มีอยู่แล้วใน User Management)
- **ทำไม่ได้:** สร้าง resource/permission ใหม่เอง (ต้องมาจาก migrate)

### 4. Role พิเศษที่ป้องกัน
- role `admin` = super role มีทุก permission, **ห้ามลบ, ห้ามแก้ permission ให้ต่ำลงจนล็อกตัวเอง**
- กันลบ role ที่มี user ใช้อยู่ (หรือเตือน/ย้าย user ก่อน)

### 5. Sidebar menu แสดงตาม permission
- user เห็นเมนูเฉพาะ resource ที่ตัวเองมี `*.read`
- ไม่มีสิทธิ์ → ไม่เห็นเมนู (ไม่ใช่เห็นแล้วกดไม่ได้)

### 6. Export — แยกจาก permission system
- export (CSV/Excel) เป็น **utility กลาง** reuse ทุก resource
- ไม่เป็น permission แยก: ใครเข้าหน้า list (มี read) ได้ ก็ export ได้
- (ถ้าอนาคตต้องคุม export แยก ค่อยเพิ่ม `resource.export` — YAGNI ตอนนี้)

---

## ผลกระทบกับของเดิม (ต้อง migrate)

- permission `access admin` เดิม → แทนด้วยระบบ `resource.action`
  - middleware `permission:access admin` ใน routes/admin.php + settings.php + User CRUD → เปลี่ยนเป็น `users.*` / per-resource
  - การเข้า /admin โดยรวม = มี permission อย่างน้อย 1 ตัว (หรือเก็บ `access admin` ไว้เป็น gate ชั้นนอก + resource permission ชั้นใน — ตัดสินตอน implement)
- User CRUD (Task 8-11) ที่ใช้ `access admin` → ผูกกับ `users.create/read/update/delete`
- seeder admin/staff → admin = ทุก permission, staff = อ่านอย่างเดียว (หรือกำหนดใหม่ตอน implement)

## แผนการทำ (แตกเป็น task ย่อย — ห้ามทำทีเดียว)

| Task | งาน | เสี่ยง |
|---|---|---|
| A | permission model: config resource + seeder generate CRUD + helper | กลาง (วางฐาน) |
| B | migrate middleware ของเดิม (User CRUD, settings) → resource.action | **สูง** (แตะของที่ทำงานอยู่) |
| C | Role Management UI: CRUD role + permission matrix | กลาง |
| D | sidebar menu แสดงตาม permission | ต่ำ |
| E | Export utility (CSV/Excel) กลาง | ต่ำ |

> แต่ละ task review + test เขียวก่อนไป task ถัดไป permission พลาด = ทั้งระบบเข้าไม่ได้

---

## ทางเลือกที่ไม่เลือก

- **Dynamic menu/resource สร้างผ่าน UI** — ปฏิเสธ: เมนูหลุดจากโค้ด, permission ลอยที่ไม่มีของจริง, ซับซ้อนเกินจำเป็นสำหรับงาน freelance (ลูกค้าจัดการสิทธิ์ ไม่ใช่สร้างฟีเจอร์)
- **คง `access admin` เดี่ยว** — ปฏิเสธ: หยาบเกินไป ลูกค้าคุมสิทธิ์ละเอียดไม่ได้
- **action ละเอียดกว่า CRUD (เพิ่ม export/approve/...)** — YAGNI: เริ่ม CRUD ก่อน เพิ่มเมื่อเจอเคสจริง
