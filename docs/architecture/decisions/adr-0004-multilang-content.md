# ADR-0004 — Multi-language Content: Per-Module Translation Table

- **สถานะ:** Accepted
- **วันที่:** 2026-06-28
- **เกี่ยวข้อง:** CLAUDE.md invariant #2 (DB-agnostic), #10 (YAGNI)

---

## ปัญหา

Content บางอย่าง (News, Product, Page) ต้องรองรับหลายภาษา (TH/EN และเผื่อ 3+)
- admin UI = ภาษาเดียว (ไม่ทำ i18n ของ interface)
- แต่ **ข้อมูล content กรอกได้หลายภาษา**
- หน้าบ้านต้องรู้ว่า content มีกี่ภาษา + ภาษาหลักคืออะไร
- ต้องออกแบบก่อนทำ News เพราะกระทบ schema (รื้อทีหลังเจ็บ)

**โจทย์หลัก = performance.** global search ข้าม module ไม่ใช่ requirement ตอนนี้ (ค่อยหาวิธีตอนต้องมีจริง)

## การตัดสินใจ

### Translation table แยกต่อ module (ไม่ใช่ polymorphic table เดียว)

```
news (ข้อมูลที่ไม่ขึ้นกับภาษา)
├── id
├── slug              (อาจ unique ต่อ locale — ดูตอน implement)
├── status            (draft/published)
├── published_at
├── author_id
└── timestamps + soft delete

news_translations (ข้อมูลต่อภาษา)
├── id
├── news_id           FK → news.id (cascade on delete)
├── locale            'th' | 'en' | ...
├── title
├── body
├── (excerpt, meta_title, meta_description ... ต่อภาษา)
├── timestamps
└── UNIQUE (news_id, locale)   ← 1 ภาษา 1 row ต่อ news
```

- 1 news → หลาย translation (th, en, ...) เพิ่มภาษา = เพิ่ม row **ไม่รื้อ schema** → รองรับ 3+ ภาษา
- module อื่น (Product) มี `product_translations` ของตัวเอง

### ทำไมเลือกแยก ไม่ใช่ polymorphic (table เดียว)

**โจทย์หลักคือ performance** → แยกชนะ:
- query ตรง + **FK + index** ช่วย (join ธรรมดา news ↔ news_translations) เร็ว
- field เป็นคอลัมน์จริง (title, body แยก) → เรียง/กรอง/validate/index ต่อ field ได้
- **DB บังคับ integrity** (FK cascade: ลบ news → คำแปลหายตาม)
- ไม่มี overhead `translatable_type` ที่ต้อง filter ทุก query
- **ไม่มีปัญหา id ชนข้าม module** — แต่ละ module มี translation table ของตัวเอง, FK ชี้ตรง

Polymorphic (translations เดียวกลาง: translatable_type+id+locale+field+value) ถูกปฏิเสธ:
- query ต่อ record ซับซ้อน (filter type+id+locale+field), ไม่มี FK constraint, value รวมทุก field → เรียง/validate ยาก, ช้ากว่า
- ข้อดีเดียวคือ global search ที่เดียว — ซึ่งไม่ใช่ requirement ตอนนี้

### Global search (อนาคต) — แยกเรื่อง ไม่ผูกมือตอนนี้

เมื่อต้องมีจริง เลือกวิธีที่เร็วกว่า polymorphic table:
- Laravel Scout + Meilisearch/Typesense (full-text engine, เร็วจริง)
- หรือ search index table ที่ sync จากทุก module
- ตัดสินตาม scale ตอนนั้น (YAGNI)

### Locale config (ภาษาที่ระบบรองรับ + ภาษาหลัก)

- รายการ locale ที่รองรับ + ภาษาหลัก (default `th`) เก็บที่ **Settings** (admin เลือกได้ต่อเว็บ)
  - บางงาน TH อย่างเดียว, บางงาน TH+EN — ต่างกันต่อลูกค้า จึงเป็น Settings ไม่ใช่ hardcode
- `config/locales.php` = รายการภาษาทั้งหมดที่ระบบ "รองรับได้" (th, en, ...) — admin เปิดใช้ subset ผ่าน Settings
- ภาษาหลัก (default locale) = ใช้เป็น fallback เมื่อ content ไม่มีภาษาที่ขอ

### หน้าบ้าน

- query content + translation ของ locale ที่ต้องการ; ถ้าไม่มี → fallback ภาษาหลัก
- รู้ว่ามีภาษาอะไรบ้าง = `$news->translations->pluck('locale')`
- ภาษาหลัก = จาก Settings

### Pattern reuse (core standard)

- trait/concern กลาง เช่น `HasTranslations` ที่ entity ใดๆ use แล้วได้ relation + helper (translate(locale), fallback)
- **News = ตัวพิสูจน์ pattern แรก** (implement ตอนทำ News module)

---

## ผลที่ตามมา

**ดี:**
- performance ดี (FK, index, query ตรง) — ตรงโจทย์หลัก
- รองรับ 3+ ภาษาไม่รื้อ schema
- DB-agnostic (table ปกติ + relation — ไม่พึ่ง JSON เฉพาะ DB) ตาม invariant #2
- integrity ระดับ DB (FK cascade)
- reuse ได้ทุก module ผ่าน trait

**ต้องระวัง:**
- global search ข้าม module ต้องทำ layer เพิ่มภายหลัง (ยอมรับ — ไม่ใช่ requirement ตอนนี้)
- ทุก module ที่ต้องแปล ต้องสร้าง `{module}_translations` ของตัวเอง (มี trait ช่วยให้เป็น pattern เดียว)

## ทางเลือกที่ไม่เลือก

- **Polymorphic translations table เดียว** — ปฏิเสธ: ช้ากว่า, ไม่มี FK, query ซับซ้อน (โจทย์หลักคือ performance)
- **JSON column** (title = {"th","en"}) — ปฏิเสธ: ขัด invariant #2 (JSON query ต่างกัน MySQL/MariaDB, ไม่ portable)
- **คอลัมน์ต่อภาษาในตารางเดียว** (title_th, title_en) — ปฏิเสธ: เพิ่มภาษาต้องแก้ schema (รื้อ), ไม่ scale
