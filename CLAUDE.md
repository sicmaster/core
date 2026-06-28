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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA_REACT) - v3
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
