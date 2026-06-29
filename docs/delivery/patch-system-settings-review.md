# Task: Patch — System Settings (post-review fixes)

> อ่าน CLAUDE.md ก่อนทำงาน
> Task นี้คือ patch จาก Claude review ผลลัพธ์ของ System Settings feature
> มี 3 จุดที่ต้องแก้ ทำให้ครบทุกจุด ห้ามแก้ไฟล์อื่นที่ไม่อยู่ในรายการนี้

---

## จุดที่ 1 — 🔴 Bug: `enabled_locales` ไม่ป้องกัน empty string จาก DB

**ไฟล์:** `app/Http/Controllers/Admin/SystemSettingController.php`

**ปัญหา:** ถ้า `enabled_locales` ใน DB เป็น empty string `''` (เช่นจาก manual edit หรือ DB bug)
`explode(',', '')` จะคืน `['']` ซึ่งเป็น array ที่มี 1 element เป็น empty string
→ Frontend checkbox จะ render ผิด, validation `in:th,en` จะ fail ด้วย error ที่ไม่ชัดเจน

**ก่อน:**
```php
$settings['enabled_locales'] = isset($settings['enabled_locales'])
    ? explode(',', $settings['enabled_locales'])
    : ['th'];
```

**หลัง:**
```php
$raw = $settings['enabled_locales'] ?? '';
$locales = array_values(array_filter(explode(',', $raw)));
$settings['enabled_locales'] = !empty($locales) ? $locales : ['th'];
```

**ตำแหน่งในไฟล์:** method `edit()` ก่อน `return Inertia::render(...)`

---

## จุดที่ 2 — 🟡 Minor: Seeder `contact_phone` ควรเป็น `null` ไม่ใช่ `''`

**ไฟล์:** `database/seeders/SystemSettingSeeder.php`

**ปัญหา:** DB column `value` เป็น `nullable text` แต่ seeder set เป็น empty string `''`
ทำให้ `setting('contact_phone')` คืน `''` แทน `null` → ไม่ consistent กับ nullable field อื่น

**ก่อน:**
```php
['key' => 'contact_phone', 'value' => ''],
```

**หลัง:**
```php
['key' => 'contact_phone', 'value' => null],
```

---

## จุดที่ 3 — 🟡 Minor: Normalize route accessor ใน TSX ให้เหมือนกัน

**ปัญหา:** มีการใช้ 2 แบบผสมกันใน codebase

| ไฟล์ | accessor ที่ใช้ |
|---|---|
| `resources/js/components/app-sidebar.tsx` | `admin.systemSettings.edit()` |
| `resources/js/pages/admin/system-settings/edit.tsx` | `admin['system-settings'].update.url()` |

ทั้งคู่ work เหมือนกัน (same object reference) แต่ไม่ consistent ทำให้อ่านยาก

**Standard ที่ตกลงใช้: camelCase** (`admin.systemSettings`) — เพราะ TypeScript-friendly และใช้ใน sidebar แล้ว

**แก้ใน `resources/js/pages/admin/system-settings/edit.tsx`:**

ค้นหา:
```ts
put(admin['system-settings'].update.url());
```

แทนด้วย:
```ts
put(admin.systemSettings.update.url());
```

และ breadcrumb ด้านล่างสุด:

ค้นหา:
```ts
href: admin['system-settings'].edit(),
```

แทนด้วย:
```ts
href: admin.systemSettings.edit(),
```

---

## Verification หลังแก้

รัน test suite:

```bash
php artisan test --filter=SystemSettingTest
```

ต้องผ่านครบทั้ง 5 test:
- `admin can view system settings`
- `staff can view system settings but cannot update`
- `admin can update system settings`
- `validation prevents invalid default locale`
- `validation prevents invalid enabled locales`

ถ้า test ผ่านทั้งหมด task เสร็จ ไม่ต้องแก้ไฟล์อื่นเพิ่ม
