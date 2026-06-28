# ADR-0002 — Authentication อยู่ใน Admin Zone (`/admin/login`)

- **สถานะ:** Accepted
- **วันที่:** 2026-06-28
- **เกี่ยวข้อง:** ADR-0001 (การแยก Frontend/Admin) — ADR นี้คือการใช้หลักเดียวกันกับ auth

---

## ปัญหา

Starter kit (Fortify) วาง auth ไว้ที่ root: `/login`, `/register`, `/forgot-password` ฯลฯ
แต่ตาม ADR-0001 เราแยก frontend/admin เป็นคนละโซน (controller/view/asset แยก)
→ auth ก็ควรเดินตามหลักเดียวกัน ไม่ใช่ login กลางที่ปนสองโซน

คำถาม: login ของหลังบ้านควรอยู่ `/login` (รวม) หรือ `/admin/login` (แยกโซน)?

---

## การตัดสินใจ

**Auth ของ admin อยู่ใต้ `/admin` ทั้งหมด** — สอดคล้องกับการแยกโซนใน ADR-0001

### Route ที่มี (admin zone)
- `GET /admin/login` — หน้า login หลังบ้าน
- `POST /admin/login` — submit
- `POST /admin/logout`
- (ถ้าจำเป็น) email verification, password confirmation

### Route ที่ตัดออก (ปิด)
- **`/register` — ปิด** ไม่ให้สมัครเองจากภายนอก
  → admin สร้าง user ผ่านหน้า **User Management** (`/admin/users`) เท่านั้น พร้อมกำหนด role
- **`/forgot-password` + `/reset-password` — ปิด**
  → ถ้า user ลืมรหัส admin รีเซ็ตให้ผ่านหน้า User Management (แก้ไข user)

### Redirect
- หลัง login สำเร็จ → `/admin/dashboard`
- หลัง logout → `/admin/login`
- guest เข้า `/admin/*` → redirect `/admin/login` (ไม่ใช่ `/login`)

### หน้าบ้าน (frontend)
- **ยังไม่มี auth** ใน v0.x — หน้าบ้านเป็นเว็บแสดงข้อมูล (ไม่มีสมาชิก)
- เมื่อเจองานที่หน้าบ้านต้องมีสมาชิกจริง → เพิ่ม auth **แยก guard** (`web` guard คนละตัวกับ admin) ไว้ที่ `/login` หรือ `/member/login` ตามงาน — เป็นคนละโซนกับ admin (ตาม ADR-0001)

---

## เหตุผล

1. **สอดคล้อง ADR-0001** — ทุกอย่างแยกโซน (controller/view/asset) แล้ว auth ก็ต้องแยก ไม่งั้นขัดกันเอง
2. **ปลอดภัยกว่า** — ปิด register/forgot ตัดทางคนนอกเข้าหลังบ้านทุกช่อง เหลือแค่ admin สร้าง user ให้
3. **ตรงกับงานจริง** — งานเว็บส่วนใหญ่ของเรา หน้าบ้านไม่มีระบบสมาชิก มีแต่ admin จัดการ
4. **user lifecycle คุมจาก admin** — สร้าง/แก้/ลบ/กำหนด role/รีเซ็ตรหัส ทำผ่าน User Management ที่เดียว (audit ง่าย คุมสิทธิ์ชัด)

---

## ผลที่ตามมา

**ดี:**
- หลังบ้านมีประตูเดียว คุมง่าย ปลอดภัย
- ไม่มีช่องให้คนนอกสมัครเข้าระบบเอง
- โครง auth พร้อมแยก frontend guard เมื่อต้องการ (ไม่ผูกกัน)

**ต้องทำ/ระวัง:**
- ต้อง override route default ของ Fortify (ปิด register/forgot, ย้าย login เข้า prefix admin)
- test ที่อ้าง `route('login')` ต้องเปลี่ยนเป็น `route('admin.login')`
- redirect ทั้งหมด (middleware, หลัง login/logout) ต้องชี้ admin zone
- React auth pages ที่ไม่ใช้ (register/forgot/reset) เอาออกหรือไม่ลิงก์ถึง

---

## ทางเลือกที่ไม่เลือก

- **`/login` กลาง แยกสิทธิ์ด้วย role** — ปฏิเสธ: ขัดหลักแยกโซน ADR-0001, ปนสองโซน
- **เปิด register หน้าหลังบ้าน** — ปฏิเสธ: ความเสี่ยงด้านความปลอดภัย, user lifecycle ควรคุมจาก admin
- **auth 2 ชุดแยก guard ตั้งแต่ตอนนี้** — ปฏิเสธ (YAGNI): หน้าบ้านยังไม่มีสมาชิก รอจนเจองานจริงค่อยเพิ่ม frontend guard
