# DECISIONS LOG

> decision เล็กๆ ที่ AI **ตัดสินเองระหว่างทางแล้วเดินต่อ** (ตามกฎ LOG ใน `/feature`)
> ไม่ใช่ ADR — ADR ใช้กับ decision สถาปัตยกรรมที่มนุษย์ approve เท่านั้น
> ของในนี้คือ naming, edge case เล็ก, เลือก lib/วิธี implement ฯลฯ — มนุษย์มา review ทีหลังได้

| วันที่ | feature | ตัดสินใจอะไร | ทางเลือกที่ไม่เลือก | เหตุผล |
|---|---|---|---|---|
| 2026-06-28 | Task 2 Sail Setup | ใช้ MariaDB 11 (ตาม sail:install default) | MySQL 8 | ตรงกับ cPanel ลูกค้าส่วนใหญ่ที่ใช้ MariaDB |
| 2026-06-28 | Task 2 Sail Setup | ตั้ง DB_DATABASE=core, DB_USERNAME=sail, DB_PASSWORD=password | ค่า default (laravel/root) | ชื่อชัดเจนกว่า ใช้ได้ทั้ง dev และเป็น template |
| 2026-06-28 | Task 3 Auth Config | rootView ยังคืน 'app' ทั้งคู่ (เตรียม logic แยกไว้แต่ใช้ view เดียวตาม R1) | คืน 'admin' สำหรับ admin.* route ทันที | ยังไม่มี admin.blade.php แยก ตาม scaffold-plan R1 |
| 2026-06-28 | Task 3 Auth Config | ใช้ `then:` callback ใน withRouting เพื่อ load admin.php | แก้ web: เป็น array หรือสร้าง RouteServiceProvider | then: เป็นวิธีที่ Laravel 13 แนะนำ สะอาดกว่า |
| 2026-06-28 | Task 3 Auth Config | admin dashboard page ชื่อ pages/admin/dashboard.tsx (ตัวเล็กทั้งหมด) | Dashboard.tsx ตัวใหญ่ | invariant cPanel-safe case-sensitivity — ต้องตรงกับ official kit ที่ใช้ตัวเล็ก |
| 2026-06-28 | Task 4 Admin Layout | reuse AppSidebar + AppLayout จาก kit แล้วเช็ค URL เพื่อแสดงเมนูต่างกัน | สร้าง AdminSidebar/AdminLayout ใหม่ | YAGNI — ไม่สร้าง component ซ้ำซ้อน, แค่แยก nav items ตาม context |
| 2026-06-28 | Task 4 Admin Layout | Users เมนู placeholder ชี้ไป admin.dashboard() ก่อน | ไม่ใส่เมนู Users | Task 8 จะสร้าง route จริง ตอนนี้เตรียมเมนูไว้ก่อน |
