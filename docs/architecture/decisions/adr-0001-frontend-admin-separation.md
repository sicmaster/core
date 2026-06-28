# ADR-0001 — การแยก Frontend / Admin (Model, Controller, View, Asset)

- **สถานะ:** Accepted
- **วันที่:** 2026-06-28
- **บริบท:** Core App เป็น single-tenant, หน้าบ้าน (Blade) + หลังบ้าน (Inertia/React) อยู่ใน Laravel app เดียว

---

## ปัญหา

หน้าบ้านกับ admin อาจใช้ "ข้อมูลชุดเดียวกัน" (เช่น News) คำถามคือแยกอย่างไรให้:
- ไม่เขียน logic ซ้ำสองที่ (แก้ที่นึงลืมอีกที่ → ข้อมูลเพี้ยน)
- แก้ฝั่งหน้าบ้านแล้วไม่ทำหลังบ้านพัง (และกลับกัน)
- หน้าบ้านไม่ต้องโหลด asset (React) ของ admin โดยไม่จำเป็น (SEO/ความเร็ว)

---

## การตัดสินใจ — แยกให้ถูก "ชั้น" ไม่ใช่แยกทุกอย่าง

### 1. Model — **ใช้ร่วมกัน 1 ตัว** (ห้ามแยก)
- `News` = ข้อมูลชุดเดียวใน DB เดียว → มี Model เดียว ใช้ทั้ง admin และ frontend
- Model ถือ: นิยาม field, relation, business rule พื้นฐาน (เช่น published ต้องมีวันที่), cast
- **ห้ามสร้าง `NewsAdmin` / `NewsFrontend` ชี้ตารางเดียวกัน** — logic จะกระจาย แก้ไม่ครบ ข้อมูลเพี้ยน
- การแก้ Model กระทบทั้งสองฝั่ง = **ถูกต้อง** เพราะเป็นการเปลี่ยน "นิยามข้อมูล" ที่ทั้งคู่ต้องเห็นตรงกัน
- กัน Model แก้ผิดด้วย **test** ทั้งสองฝั่ง (ไม่ใช่ด้วยการแยก Model)

### 2. "มุมมอง" ที่ต่างกัน → ใช้ **Query Scope** ใน Model เดียว
- หน้าบ้านเห็นเฉพาะ published: `News::published()`
- admin เห็นทั้งหมดรวม draft: `News::query()` / ไม่ใส่ scope
- Model เดียว มุมมองต่างกันผ่าน scope — ไม่แยก Model

### 3. Controller — **แยก folder คนละตัว**
- `app/Http/Controllers/Frontend/NewsController` — แสดงผลอย่างเดียว (index/show)
- `app/Http/Controllers/Admin/NewsController` — สร้าง/แก้/ลบ (CRUD)
- **แก้ logic หน้าบ้าน = แก้ Frontend/ ไม่แตะ Admin/** → ความเสี่ยง "แก้หน้าบ้านทำหลังบ้านพัง" หายตรงนี้

### 4. View — **แยกโดยธรรมชาติ**
- หน้าบ้าน = Blade (`resources/views/...`) — server-render, SEO
- admin = React/Inertia (`resources/js/pages/admin/...`)
- คนละไฟล์อยู่แล้ว

### 5. Asset — **แยก Vite entry** (สำคัญ ทำตอน v0.1)
- หน้าบ้านต้อง **ไม่โหลด React bundle ของ admin** (ช้า + SEO แย่)
- แก้ CSS admin ต้องไม่กระทบหน้าบ้าน
- ใช้ Vite **multiple entry points**:
  ```
  resources/css/app.css        → admin (Tailwind + shadcn)
  resources/css/frontend.css   → หน้าบ้าน (Tailwind เบา)
  resources/js/app.tsx         → admin (React/Inertia)
  resources/js/frontend.js     → หน้าบ้าน (JS เล็กน้อย ถ้าต้องมี)
  ```
- Blade หน้าบ้านโหลดแค่ `frontend.css`; admin โหลด `app.tsx`
- **ห้ามใช้ CSS/JS ก้อนเดียวร่วมกันสองฝั่ง**

### 6. (เผื่ออนาคต) Service/Action layer — **YAGNI ยังไม่ทำ**
- ถ้า logic หน้าบ้าน/หลังบ้านต่างกัน *มาก* ค่อยแยก Action (เช่น `PublishNewsAction`) ออกจาก Controller
- รอจนเจอเคสจริง อย่าทำเผื่อ

---

## ทำเมื่อไหร่ (กันสับสน scope)

| ส่วน | ทำตอน |
|---|---|
| Model ใช้ร่วม + scope | ตอนทำ CRUD แต่ละ entity (v0.0.1 เป็นต้นไป) |
| Controller แยก Admin/Frontend | โครงวางแล้ว (Task 3); ใช้จริงตอนทำ entity |
| View Blade หน้าบ้าน | **v0.1** (v0.0.1 หน้าบ้านยังเป็น welcome ของ kit) |
| **Asset แยก Vite entry** | **v0.1** (พร้อมกับทำหน้าบ้าน Blade — อย่าทำก่อน = YAGNI) |

---

## ผลที่ตามมา

**ดี:**
- ข้อมูลมี source เดียว (Model) — ไม่เพี้ยน
- แก้ฝั่งหนึ่งไม่กระทบอีกฝั่ง (Controller/View/Asset แยก)
- หน้าบ้านเบา + SEO ดี (ไม่โหลด React admin)

**ต้องระวัง:**
- การแก้ Model = กระทบทั้งสองฝั่ง (ตั้งใจให้เป็นแบบนั้น) → ต้องมี test ทั้งสองฝั่ง
- ต้องไม่เผลอ import asset ข้ามฝั่ง (admin ↔ frontend)

---

## ทางเลือกที่ไม่เลือก

- **แยก Model สองตัว** — ปฏิเสธ: logic กระจาย, ข้อมูลเพี้ยน, แก้ไม่ครบ
- **Asset ก้อนเดียวร่วมกัน** — ปฏิเสธ: หน้าบ้านช้า, SEO แย่, แก้ CSS ข้ามฝั่ง
- **แยกเป็นคนละ Laravel project/repo** — ปฏิเสธ: เกินจำเป็นสำหรับ single-tenant freelance, deploy ยุ่ง (ดูเหตุผลใน CLAUDE.md)
