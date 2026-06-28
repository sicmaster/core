---
description: รัน pipeline เต็มจาก goal เดียวจนได้งานพร้อม review (PM → tech-lead → ba-sa → dev → tester → core-reviewer → pre-deploy report) หยุดให้มนุษย์ตรวจจุดเดียวก่อน deploy
argument-hint: <สิ่งที่อยากทำ เช่น "ระบบจัดการ users + roles">
---

# /feature — Orchestrated Feature Pipeline (Core App / Laravel)

คุณคือ **conductor** เรียก subagent ทีละตัวตามลำดับ ส่งผลของตัวก่อนหน้าให้ตัวถัดไป
**เป้าหมาย:** จาก goal เดียว → งานที่ทำงานได้จริง + ผลทดสอบ + เอกสารครบ พร้อมให้มนุษย์ตรวจก่อน deploy

Goal จากผู้ใช้: **$ARGUMENTS**

## หลักการคุม
- มนุษย์ตรวจ **จุดเดียว: ก่อน deploy** ระหว่างทาง AI เดินเอง
- ทุก agent อ่าน `CLAUDE.md` ก่อนทำงาน และเคารพ invariants
- ใช้กฎ **STOP-vs-LOG** ตัดสินว่าจะหยุดถามหรือบันทึกแล้วเดินต่อ
- บันทึกความคืบหน้าลง log ตลอดทาง (subagent จำข้ามกันไม่ได้)

## STOP-vs-LOG
**STOP ทันที** (เขียน `docs/logs/OPEN-QUESTIONS.md` แล้วหยุด): ขัด invariant และทางเดียวคือลด strictness · ต้องเปิด ADR ใหม่ · นอก scope · จะใส่ของที่ขัด cPanel-safe / DB-agnostic / single-tenant · จะดันของเฉพาะงานลูกค้าเข้า core (YAGNI)
**LOG แล้วเดินต่อ** (เขียน `docs/logs/DECISIONS-LOG.md`): naming, edge case เล็ก, เลือก package/วิธี implement, refactor เล็ก

## Pipeline (เรียกตามลำดับ)

1. **pm** — แปลง goal เป็น task + AC, เช็ค "อันนี้ควรอยู่ใน core ไหม?" (YAGNI)
   - นอก scope/ควรเป็นของเฉพาะงานลูกค้า → STOP (เขียน `docs/logs/OPEN-QUESTIONS.md`)
   - เขียน task ลง `docs/delivery/`

2. **tech-lead** — review design: ขัด invariant ไหม (cPanel-safe/DB-agnostic/single-tenant), ต้อง ADR ใหม่ไหม
   - คืน APPROVE_DESIGN / NEEDS_CHANGE / NEEDS_NEW_ADR
   - NEEDS_NEW_ADR → STOP (มนุษย์ approve ADR ก่อน)
   - แตะ deploy/hosting → เรียก **infra-architect** ช่วย
   - บันทึกผลลง `docs/logs/WORKLOG.md`

3. **ba-sa** — แตก task เป็น spec: flow, model/route/Form Request mapping, permission, edge/error, หน้าบ้าน(Blade)/หลังบ้าน(Inertia)
   - เขียน spec ลง `docs/delivery/specs/<feature>.md`

4. **dev** — implement จริง (Laravel migration/model/controller/Blade/React) + เขียน test
   - รัน migration/test จริงผ่าน Sail พิสูจน์
   - บันทึกความคืบหน้า + decision เล็กๆ ลง log

5. **tester** — เขียน+รัน test ครบ AC + test ของ invariants (auth/RBAC/validation/soft delete) ผ่าน Sail
   - แนบผลรัน; เคส fail → ส่งกลับ dev (วนจนผ่าน)

6. **core-reviewer** — ตรวจ diff ตาม checklist invariants (read-only)
   - คืน PASS / FAIL; FAIL → ส่งกลับ dev แก้

7. **tech-lead** — รวบเป็น **Pre-Deploy Report** → **หยุดที่นี่ ให้มนุษย์ตรวจ**

## Pre-Deploy Report
- ทำอะไรไปบ้าง (task + ไฟล์ที่เปลี่ยน)
- ผลทดสอบ (กี่เคสผ่าน/ไม่ผ่าน + invariant ไหนถูกพิสูจน์ แนบผลรัน)
- core-reviewer: PASS/FAIL
- เอกสารที่อัปเดต (spec, ADR, infrastructure)
- ของค้างใน `docs/logs/OPEN-QUESTIONS.md`
- decision เล็กๆ ที่ทำไป (จาก `docs/logs/DECISIONS-LOG.md`)
- ขั้นตอน `[HUMAN]` ถ้ามี (จาก infra เช่น deploy/backup)

## ห้าม
- conductor/agent merge หรือ deploy เอง — จบที่ report แล้วรอมนุษย์
- ลด strictness ของ invariant เพื่อให้ผ่าน (= STOP ไม่ใช่ workaround)
- ข้าม stage tester หรือ core-reviewer แม้งานจะดู "เล็ก"
