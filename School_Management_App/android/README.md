# DAV School — Android apps (v2.0.0)

One Kotlin + Jetpack Compose (Material 3) codebase that builds **four separate apps**:

| App | Flavor | Package | Who signs in |
|---|---|---|---|
| DAV School Admin | `school` | `com.davdugda.sms.school` | Principal / office (admin) |
| DAV Staff | `staff` | `com.davdugda.sms.staff` | Teachers, accountant, librarian, other staff |
| DAV Parent | `parent` | `com.davdugda.sms.parent` | Parents (switch between children) |
| DAV Student | `student` | `com.davdugda.sms.student` | Students |

Default server: `https://dav-dugda-sms.sites.tab.bot/api/v1/` (change it on the login screen → *Server settings*, or build with `-PapiBaseUrl=https://your-domain/api/v1/`).

## Folder layout — one folder per management area

```
core/src/main/java/com/davdugda/sms/
  core/                 shared plumbing
    data/               API (Retrofit), models, module registry cache, file downloads/uploads
    nav/                app shell, drawer/menu, routes, ModuleArea registry
    ui/                 theme + shared components
  modules/
    dashboard/  auth/  profile/  search/  settings/  reports/
    students/  parents/  teachers/  staff/  admissions/
    academics/  timetable/  homework/  attendance/  exams/  certificates/
    fees/  accounts/  payroll/  inventory/  hostel/  transport/  library/
    front_office/  communication/
    generic/            the module engine every area uses
app/                    the 4 flavors (name, colour, icon) + UI flow test
```

Each `modules/<area>/<Area>Module.kt` says which server modules and screens belong to that area
(e.g. `hostel` → hostels, hostel_rooms, hostel_allocations). Areas with special workflows keep their
own screens there too (attendance taking, marks grid, fee collection, payroll run, promotion, reports…).

## How the apps cover every module

The server publishes its modules (`GET meta/modules`) and each person's menu (`GET meta/menu`).
The **generic engine** (`modules/generic/`) turns that into screens for every module, so new
modules on the server show up in the apps without an app update:

- **List** with search, filters, paging, Excel/PDF/CSV export, import (where allowed).
- **Record page** with all fields, photo, print buttons (receipt, report card, ID card, admit card,
  certificate, salary slip …), actions (e.g. *Cancel receipt* with a reason), and **History**
  (admin: who changed what, old → new).
- **Add / Edit form** built from the field types (text, number, money, date, select, yes/no,
  links to other records, file/photo upload). Fields marked permanent (admission no., receipt no. …)
  can be set once and are shown locked afterwards.
- **Locked records** (issued receipts, issued certificates, results after lock, paid salary slips,
  promotion history, the audit log) show a red 🔒 *Locked* badge and the reason; Edit/Delete are
  hidden and the server refuses changes (HTTP 423).

Also: dashboard charts, notifications with unread badge, global search, notices with read status,
student photo upload, attendance report (PDF/Excel), fee reminders (in-app + free WhatsApp links),
fee generation, payroll run, promotion / session rollover, Excel import wizard, backup download,
audit log. The Principal's name (from the server's school profile) is shown on the admin dashboard.

PDFs and Excel files are downloaded with the sign-in token, saved to *Downloads/DAV School*
(Android 10+) and opened in the phone's PDF/Excel viewer.

With an older (Phase 1) server without `meta/*`, the apps fall back to their built-in screens.

## Build

Needs JDK 17 and the Android SDK (platform 34, build-tools 34).

```bash
cd android
./gradlew assembleDebug            # all 4 APKs → app/build/outputs/apk/<flavor>/debug/
./gradlew assembleSchoolDebug      # just one app
```

## Tests

```bash
./gradlew :core:testDebugUnitTest                       # API contract: decodes real server responses
./gradlew :app:testSchoolDebugUnitTest -PtestApi=http://127.0.0.1:8090/api/v1/   # UI flow (Robolectric)
```

The UI flow test starts the real app, signs in with the flavor's demo account against a running backend,
opens modules, a record, a locked receipt, notifications and search, and saves screenshots to
`app/build/screenshots/`.
