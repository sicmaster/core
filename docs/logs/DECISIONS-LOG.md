# DECISIONS LOG

> decision เล็กๆ ที่ AI **ตัดสินเองระหว่างทางแล้วเดินต่อ** (ตามกฎ LOG ใน `/feature`)
> ไม่ใช่ ADR — ADR ใช้กับ decision สถาปัตยกรรมที่มนุษย์ approve เท่านั้น
> ของในนี้คือ naming, edge case เล็ก, เลือก lib/วิธี implement ฯลฯ — มนุษย์มา review ทีหลังได้

| วันที่ | feature | ตัดสินใจอะไร | ทางเลือกที่ไม่เลือก | เหตุผล |
|---|---|---|---|---|
| 2026-06-28 | Task 2 Sail Setup | ใช้ MariaDB 11 (ตาม sail:install default) | MySQL 8 | ตรงกับ cPanel ลูกค้าส่วนใหญ่ที่ใช้ MariaDB |
| 2026-06-28 | Task 2 Sail Setup | ตั้ง DB_DATABASE=core, DB_USERNAME=sail, DB_PASSWORD=password | ค่า default (laravel/root) | ชื่อชัดเจนกว่า ใช้ได้ทั้ง dev และเป็น template |
