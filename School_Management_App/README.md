# DAV School Management System – v2.0

Complete school software for **DAV Public School, Dugda** (Dugda Coal Washery (BCCL), P.O. Dugda, Distt. Bokaro, Jharkhand 828404 ·
CBSE Affiliation No. 3430146) · Principal **Prasanjeet Kumar Paul**.

One server (Laravel API + public website) controls everything; every platform talks to it.
100 % free and open-source tools – no paid service is needed to run it.

| Part | What it is | Folder | Get it |
|---|---|---|---|
| **Server (API)** | Laravel 12 REST API, 49 management modules, PDFs, Excel import/export, audit trail | `backend/` | see `docs/GO_LIVE_GUIDE.md` |
| **Public school website + Website CMS** | Modern animated school website at `/`, its own admin panel at `/website-admin` (separate login) | `website/` | comes with the server |
| **Android apps (4)** | School Admin · Staff · Parent · Student (Kotlin + Compose) | `android/` | [releases/](releases/README.md) |
| **Windows software** | Full management software for the office PC (Electron + Vue) – installer `.exe` + portable `.zip` | `windows/` | [releases/](releases/README.md) |
| **iPhone apps (source)** | SwiftUI source for the same 4 apps (needs a Mac to build) | `ios/` | `ios/README.md` |

**Live demo:** website https://dav-dugda-sms.sites.tab.bot/ · API `https://dav-dugda-sms.sites.tab.bot/api/v1/` ·
downloads https://dav-dugda-sms.sites.tab.bot/downloads/

## Go live
Step-by-step beginner guide (free hosting): [`docs/GO_LIVE_GUIDE.md`](docs/GO_LIVE_GUIDE.md) (PDF: `docs/DAV_Go_Live_Guide.pdf`).

## Demo logins

| Who | Login | Password | Use in |
|---|---|---|---|
| **Principal – Prasanjeet Kumar Paul** | `principal@davdugda.in` | `principal123` | School Admin app, Windows software |
| School office (admin) | `admin@davdugda.in` | `admin123` | School Admin app, Windows software |
| Teacher | `teacher@davdugda.in` | `teacher123` | Staff app, Windows software |
| Accountant | `accountant@davdugda.in` | `accountant123` | Staff app, Windows software |
| Librarian | `librarian@davdugda.in` | `librarian123` | Staff app, Windows software |
| Parent (2 children) | `parent@davdugda.in` or `9431000001` | `parent123` | Parent app |
| Student (Class 8-A) | `student@davdugda.in` | `student123` | Student app |
| **Website CMS** | `webadmin@davdugda.in` | `webadmin123` | https://YOUR-SITE/website-admin |

Change every password before real use. For a real school with no demo data run
`php artisan sms:install principal@davdugda.in "StrongPassword"` (see section 3).

## Modules (same names & folders on every platform)

Students · Parents · Teachers · Staff · Admissions (enquiries → admission) · Academics (sessions, classes, sections, subjects, syllabus,
study material) · Timetable · Homework · Attendance (students & staff, monthly register) · Leave · Exams (schedules, marks, CBSE grades,
report cards, admit cards, results lock) · Certificates (TC, character, bonafide) & Promotion · Fees (fee heads, class-wise fee structure,
concessions, invoices, fines, collection, receipts, reminders) · Accounts (income/expense vouchers) · Payroll (salary structure, payslips)
· Inventory & Assets · Hostel · Transport (routes, vehicles, documents expiry) · Library · Front office (visitors, postal, calls) ·
Communication (notices with read status, events & holidays, messages, complaints, notifications) · Reports & charts · Settings, backup,
Excel import · Audit log.

Code layout: `backend/app/Modules/<Module>/` (models, controllers, `module.php` definition), `android/core/.../modules/<area>/`,
`windows/renderer/src/modules/<area>/` + `windows/src/main/modules/<feature>/`, `ios/DAVSchool/Modules/<Area>/`.

## Editing system & permanent records

Every module can be edited by the roles allowed to, and **every add/edit/delete is written to the audit log**
(who, when, old value → new value). Open any record → *History*.

These records are **permanent** – the server refuses to change them (HTTP 423 with the reason) and every app shows a red *Locked* badge:

| Record | Rule |
|---|---|
| Fee receipts | Never edited/deleted. Only **Cancel receipt** (with a reason) – it stays on record as CANCELLED and the amount returns to dues. |
| Issued certificates (TC etc.) | Locked once issued; can only be cancelled with a reason. |
| Exam marks & results | Locked when the principal clicks **Lock results**. |
| Paid payslips | Locked once marked paid. |
| Promotion history, stock movements, audit log | Always permanent. |
| Admission no., employee ID, invoice no., receipt no. | Can never be changed after creation. |

## Printing (PDF) & Excel

PDFs carry the school logo, affiliation number and the principal's name: fee receipt (school + parent copy), invoice, fee statement,
report card, student & staff ID cards, admit cards, TC / character / bonafide certificates, salary slip, monthly attendance register,
and a PDF of any list. Every list exports to Excel/CSV; registers can be imported from Excel – 29 ready templates in
`docs/import_templates/` (each has a *How to fill* sheet; you get a preview before anything is saved).

---

## 2. Features by app (Phase 1 detail)

**School Admin app** (role `admin`)
- Dashboard: students, staff, parents, today's attendance, fees collected this month, fees due, alerts (pending leave, overdue books)
- Students: search/filter by class & section, full profile (attendance %, fee dues, parent, bus route), **admit student** (auto admission no., roll no., student login, parent login by mobile), remove
- Staff and Parents: list, search, add (teacher / accountant / librarian / other staff / extra admin), enable/disable login
- Classes & Subjects: classes with sections, class teachers, subjects & subject teachers; add/delete
- Take attendance (any class), staff attendance, timetable editor (clash detection for section and teacher)
- Exams: create, publish/unpublish results, date sheets; marks entry; report cards for any student (percentage, CBSE grade, GPA, rank in section)
- Fees: collection summary, invoices (due/paid), **bill a whole class or section** at once, record payments (cash/UPI/card/bank/cheque) with receipt numbers, payment history
- Homework (all classes), notices (to everyone / staff / parents / students, pin), events & holidays calendar
- Library: catalogue, add books, issue to students, returns with automatic late fine
- Transport: routes, vehicle, driver, stops, timings, fare, students per route
- Leave approvals (approved student leave is written into attendance automatically)
- Messages, school settings (profile, sessions, grade scale, fee types)

**Staff app** (roles `teacher`, `accountant`, `librarian`, `staff`) – screens adapt to the job
- Teacher: today's classes, my timetable (or any class), take attendance for own classes, enter marks for own subjects/classes, give homework and check submissions, students list, report cards, exams, review leave of own students, post notices to parents/students
- Accountant: fee dashboard, invoices, record payments, receipts
- Librarian: catalogue, issue/return, overdue list
- Everyone: my attendance, apply for leave, notices, events, transport, library (my books), messages, profile

**Parent app** (role `parent`)
- Child switcher (one login for all children), dashboard with attendance %, fee due, pending homework, today's status
- Attendance calendar per month, report cards (only after the school publishes results), fees & receipts,
  homework, timetable, exam date sheet, notices, events, transport (child's bus highlighted), apply leave for a child,
  message class/subject teachers and the office

**Student app** (role `student`)
- Dashboard, timetable, attendance calendar, homework (submit answers, see teacher feedback/grade), results,
  exam date sheet, fees, library (my books & due dates), notices, events, leave, transport, messages

---

## 3. Backend (Laravel API)

Requirements: PHP 8.2+ with `pdo_sqlite` or `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`; Composer.

### Local

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite            # default DB is SQLite (set DB_CONNECTION=mysql for MySQL)
php artisan migrate --seed                 # creates all tables + the demo school
php artisan serve --host=0.0.0.0 --port=8000
```

- Android emulator: server address `http://10.0.2.2:8000`
- Real phone on the same Wi-Fi: `http://<your-PC-IP>:8000` (e.g. `http://192.168.1.10:8000`)

### Real school (no demo data)

```bash
php artisan migrate --force
php artisan sms:install principal@davdugda.in "a-strong-password"   # name defaults to Prasanjeet Kumar Paul
```

Then sign in to the **School Admin** app with that email and build the school from the app
(classes → staff → students → timetable → fees ...).

### Free hosting (no credit card) – alwaysdata.com

The free plan (100 MB, PHP 8, MySQL, SSH, HTTPS on `yourname.alwaysdata.net`) is enough for this API.

1. Sign up at **alwaysdata.com** (email only) and pick the **Free** plan. Choose an account name, e.g. `davdugda`.
2. *Databases → MySQL*: create a database (e.g. `davdugda_sms`) and a user.
3. *Remote access → SSH*: enable password login, then connect: `ssh davdugda@ssh-davdugda.alwaysdata.net`
4. On the server:
   ```bash
   git clone https://github.com/RIP09/dav_dugda_bokaro_jharkhand_828404.git
   cd dav_dugda_bokaro_jharkhand_828404/School_Management_App/backend
   composer install --no-dev --optimize-autoloader
   cp .env.example .env && php artisan key:generate
   nano .env    # APP_ENV=production, APP_DEBUG=false, APP_URL=https://davdugda.alwaysdata.net,
                # DB_CONNECTION=mysql, DB_HOST=mysql-davdugda.alwaysdata.net, DB_PORT=3306,
                # DB_DATABASE=davdugda_sms, DB_USERNAME=..., DB_PASSWORD=...
   php artisan migrate --force --seed          # demo data, or: migrate --force + sms:install (above)
   php artisan config:cache && php artisan route:cache
   ```
5. *Web → Sites*: edit the default site → type **PHP**, root directory
   `/home/davdugda/dav_dugda_bokaro_jharkhand_828404/School_Management_App/backend/public`, PHP 8.2 or newer.
6. In each app: **Server settings** → `https://davdugda.alwaysdata.net` (or build the APKs with it, section 4).

To update later: `git pull && composer install --no-dev -o && php artisan migrate --force && php artisan config:cache`.

Avoid free hosts that put a browser "JavaScript check" in front of every request (InfinityFree and similar):
mobile apps calling an API cannot pass it.

### Any Linux server / container

`backend/start.sh` runs the API on `$PORT` (default 8080) with the system PHP, or with a static PHP binary in
`backend/bin/php` if the server has none (download one from https://dl.static-php.dev/static-php-cli/common/ →
`php-8.4.x-cli-linux-x86_64.tar.gz`). It creates and seeds the SQLite database on first start.
This is how the public demo server runs.

### API overview

Base URL `/api/v1`, JSON everywhere (`{"data": ...}`), `Authorization: Bearer <token>` after login.

| Area | Endpoints |
|---|---|
| Auth | `POST auth/login {login, password, app}` · `GET auth/me` · `POST auth/logout` · `POST auth/change-password` · `PUT auth/profile` |
| Home | `GET dashboard[?student_id]` (role-aware) · `GET school` |
| Academic | `classes` · `sections` · `subjects` · `academic-years` (GET all roles; POST/PUT/DELETE admin) |
| People | `students` (CRUD, `POST students/promote`) · `users?role=` (staff/parents/admins, admin CRUD, `toggle-active`) |
| Attendance | `GET/POST attendance/students` · `GET students/{id}/attendance?month=` · `GET attendance/my` · `GET attendance/class-summary` · `GET/POST attendance/staff` |
| Timetable | `GET timetable?section_id= / ?mine=1 / ?student_id=` · `POST/DELETE timetable` |
| Exams | `exams` (CRUD, `publish`) · `exams/{id}/schedules` · `grades` · `GET/POST marks` · `GET students/{id}/report-card` · `GET report-card/my` |
| Fees | `fee-types` · `GET/POST invoices` (single student or whole class) · `POST invoices/{id}/payments` · `GET payments` · `GET fees/summary` |
| Homework | `GET/POST homework` · `POST homework/{id}/submit` · `GET homework/{id}/submissions` · `POST submissions/{id}/review` |
| Communication | `notices` · `events` · `messages` (`?box=inbox|sent`, `contacts`, `unread`) |
| Library | `library/books` · `library/issues` · `POST library/issues/{id}/return` |
| Transport | `transport/routes` · `transport/routes/{id}/students` |
| Leave | `GET/POST leaves` · `POST leaves/{id}/review` · `DELETE leaves/{id}` |

Role rules are enforced on the server (`role:` middleware + ownership checks): parents only see their own
children, students only themselves, teachers only take attendance / enter marks for their own classes and subjects,
fees are admin/accountant, library writes are admin/librarian, and only the admin can create accounts or change structure.

---

## 4. Android apps

Requirements: JDK 17, Android SDK (platform 34, build-tools 34), or just Android Studio (Koala or newer).

```bash
cd android
./gradlew assembleDebug                      # all 4 apps
./gradlew assembleSchoolDebug                # just one (School | Staff | Parent | Student)
./gradlew assembleDebug -PapiBaseUrl=https://davdugda.alwaysdata.net/api/v1/   # bake in your own server
```

APKs: `android/app/build/outputs/apk/<flavor>/debug/app-<flavor>-debug.apk`.
For the Play Store, create a keystore, add a `signingConfig` for `release`, and run `./gradlew bundleRelease`.

Architecture: single-activity Jetpack Compose UI, Material 3 with a per-app brand colour (light & dark),
MVVM (one ViewModel per screen, `core/.../ui/BaseViewModel.kt`), Retrofit + OkHttp + kotlinx.serialization,
a small manual DI container (`AppContainer`), token + server address stored on the device.
The list of screens each app shows is in `core/src/main/java/com/davdugda/sms/core/nav/Features.kt`.

---

## 5. Tests (all run during development)

| Test | Command | What it proves |
|---|---|---|
| API smoke test | `php artisan migrate:fresh --seed && php artisan serve` then `python3 backend/tests/smoke_api.py` | 138 checks across admin, teacher, accountant, librarian, parent and student: every module, plus the blocks (wrong password, wrong app, parent opening another child, teacher billing, marks above max, overpayment, timetable clash, unpublished results, revoked token) |
| API contract test | `cd android && ./gradlew :core:testDebugUnitTest` | 67 real API responses (every role) decode with the apps' models and JSON settings |
| App flow test | backend running on `127.0.0.1:8000`, then `./gradlew :app:testSchoolDebugUnitTest` (and Staff / Parent / Student) | each real app starts (Robolectric), signs in with its demo account, opens its main screens, saves screenshots to `android/app/build/screenshots/` |

---

## 6. Notes & next steps

- Online fee payment (UPI gateway such as Razorpay) is not wired in; payments are recorded by the office/accountant.
  The `payments.method` and `reference` fields are ready for a gateway callback.
- Push notifications (Firebase) and SMS alerts are not included; notices, messages and dashboard alerts cover it in-app.
- Photo upload for students/staff is not in the apps yet (the API has the `photo` field).
- iOS: the API is ready for an iOS app; this repo contains Android only.
