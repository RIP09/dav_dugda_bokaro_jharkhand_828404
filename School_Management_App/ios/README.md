# DAV School – iOS apps (SwiftUI)

Native iPhone/iPad source for the **DAV Public School, Dugda** management system — the same four apps as Android,
built from one codebase:

| App (Xcode target / scheme) | Bundle id | Who signs in |
|---|---|---|
| **DAV School Admin** (`DAVSchool`) | `com.davdugda.sms.school` | Principal (Prasanjeet Kumar Paul) & office |
| **DAV Staff** (`DAVStaff`) | `com.davdugda.sms.staff` | Teachers, accountant, librarian, other staff |
| **DAV Parent** (`DAVParent`) | `com.davdugda.sms.parent` | Parents (switch between children) |
| **DAV Student** (`DAVStudent`) | `com.davdugda.sms.student` | Students |

Each target's `Info.plist` sets `DAVAppRole` (school / staff / parent / student). The app sends it as `app=` on login,
exactly like the Android apps, and the server only lets the right roles in.

> **Important – honest status.** This folder is source code. An iPhone app can only be compiled, signed and installed
> with **a Mac running Xcode**. It was written and checked on Linux (see “What was checked” below) but it has **not**
> been compiled with Xcode or run on an iPhone yet.
> Without a Mac there is no way to install a native iOS app. **Today the working paths are the Android apps, the
> Windows app, and on iPhone the school website added to the home screen.**

## What's inside

- **Every management module** through one generic engine driven by the server's module list (`meta/modules`):
  list with search, filters, paging, pull-to-refresh → detail → add / edit form (text, numbers, ₹ money, dates,
  dropdowns, yes/no, links to other records, photo & file upload) → delete → Excel/PDF/CSV export.
- **Edit system with locks.** Wrong data can be edited by the right person; every change goes to the history
  (who / when / old → new, admins can open it on any record). **Permanent records are locked** — issued fee receipts,
  issued certificates (TC, character, bonafide), locked exam results, paid salary slips, promotion history, the audit log.
  Locked records show a lock and the reason, and Edit/Delete are turned off. Receipts and certificates can only be
  *cancelled* with a reason (that is saved too).
- **Print / PDF**: receipts, invoices, report cards, ID cards, admit cards, certificates, salary slips, attendance
  registers, fee statements — opened in the built-in viewer with print & share (WhatsApp, Files, AirDrop…).
- **Custom screens**: login (with “Use demo account”), dashboard with cards and charts, menu, take attendance
  (one tap per student), staff attendance, attendance calendar & monthly report, marks entry (lock-aware),
  report cards, fees (invoices, collect payment, receipts, due reminders with free WhatsApp links, monthly invoice
  generation), homework (give / submit / check), timetable, notices (read status), messages (parent ↔ teacher),
  notifications, global search, profile (photo, change password), child switcher for parents, edit-history log,
  Excel import (move offline registers in), backup download, payroll run, promotion to the next session, settings.

## Folder layout (one folder per area – same names as backend / web / Android / Windows)

```
ios/
├── project.yml                 XcodeGen spec (4 targets)
├── DAVSchool.xcodeproj         generated from project.yml (see below)
├── Targets/{School,Staff,Parent,Student}/   Info.plist + Assets (app icon, colour) per app
├── DAVSchool/
│   ├── App/                    app entry, tabs, navigation routes
│   ├── Core/
│   │   ├── Networking/         APIClient (URLSession), every endpoint, JSON
│   │   ├── Models/             Codable models (tolerant decoding)
│   │   ├── Session/            login state, Keychain token, app role, area registry
│   │   ├── DesignSystem/       colours, icons
│   │   └── Components/         lock badge, PDF viewer, pickers, cards, empty/error states
│   └── Modules/
│       ├── Admissions/  Students/  Parents/  Teachers/  Staff/  Academics/
│       ├── Attendance/  Timetable/  Homework/  Exams/  Certificates/
│       ├── Fees/  Accounts/  Payroll/  Library/  Transport/  Hostel/  Inventory/  FrontOffice/
│       ├── Communication/      notices, messages, notifications, events & holidays
│       ├── Reports/            edit history, Excel import, backup
│       ├── Settings/  Dashboard/  Auth/  Profile/  Search/
│       └── Generic/            the list / detail / form / history engine used by every module
└── Tools/LinuxCheck/           checks the Core code against a live server (Linux or Mac)
```

Each `Modules/<Area>/<Area>Module.swift` declares which server modules and screens belong to that area and which PDFs
its records offer; the area's screens live next to it.

## Requirements

- A **Mac** with **Xcode 15 or newer** (free from the Mac App Store). iOS 16 or newer on the iPhone.
- To install on **your own iPhone**: a **free Apple ID** is enough (Xcode → Settings → Accounts). Apps installed this
  way stop opening after **7 days** — just press Run again in Xcode to reinstall.
- For the **App Store / TestFlight** (to give the app to all parents) you need the paid Apple Developer Program
  (US$99 per year, about ₹8,000+). Not needed for testing.
- No third-party libraries. Only Apple's frameworks (SwiftUI, Swift Charts, QuickLook, PhotosUI).

## Build & run (on a Mac)

1. Copy the `School_Management_App/ios` folder to the Mac (or `git clone` the repo).
2. Open **`ios/DAVSchool.xcodeproj`** in Xcode.
   *If Xcode says the project can't be opened, regenerate it:* `brew install xcodegen` then `cd ios && xcodegen generate`.
3. At the top of Xcode pick the scheme — **DAVSchool**, **DAVStaff**, **DAVParent** or **DAVStudent** — and an iPhone
   simulator, then press **▶ Run**.
4. To run on your iPhone: connect it with a cable, choose it as the run destination, open the target's
   **Signing & Capabilities** tab, tick *Automatically manage signing* and pick your Apple ID team.
   If the bundle id is taken, change it (e.g. `com.yourname.dav.parent`). On the iPhone allow the developer under
   *Settings → General → VPN & Device Management*.
5. Sign in with a demo account (tap **Use demo account**):
   - School Admin: `principal@davdugda.in` / `principal123` (Prasanjeet Kumar Paul) — or `admin@davdugda.in` / `admin123`
   - Staff: `teacher@davdugda.in` / `teacher123`, `accountant@davdugda.in` / `accountant123`, `librarian@davdugda.in` / `librarian123`
   - Parent: `parent@davdugda.in` / `parent123` · Student: `student@davdugda.in` / `student123`

## Changing the server

The apps connect to the demo server `https://dav-dugda-sms.sites.tab.bot/api/v1/` by default.
- **In the app:** login screen → **Server settings** → type your school's address (e.g. `https://dav.yourdomain.in`)
  → **Test connection** → **Save**. `/api/v1/` is added automatically.
- **Permanently in the code:** change `defaultServer` in `DAVSchool/Core/Session/AppConfig.swift`.

## What was checked (and what wasn't)

Checked on Linux (Debian 12) with the free Swift 5.10 toolchain:
- The Foundation-only Core — `Core/Networking`, `Core/Models`, `AppConfig`, `ModuleArea`, `KeychainStore` and every
  `Modules/*/<Area>Module.swift` — **type-checks and compiles** with `swiftc`.
- `Tools/LinuxCheck/run.sh` compiles that Core with a test program, signs in to a real v2 server as each role and
  decodes the real replies with the app's own models (login, me, meta/modules, meta/menu, dashboard, notifications,
  search, notices, events, homework, exams, attendance sheets and reports, marks sheets, report cards, fees, payments,
  timetable, audit), lists + opens + reads the history of every module in the registry, checks that editing an issued
  receipt is refused with 423 Locked, and downloads a receipt PDF with the login token.
  **Last run (27 Oct 2026, local v2 server, all 7 demo accounts): 207 passed, 0 failed, 2 skipped** (accountant and
  librarian don't take attendance). All 48 server modules decoded; locked rows seen in payments, certificates, exams,
  stock movements and the audit log. The full output is in `Tools/LinuxCheck/last-run.txt`.
- Every Swift file passes `swiftc -parse` (syntax).
- `DAVSchool.xcodeproj` was generated on Linux by XcodeGen 2.42.0 (built from source) from `project.yml`: 4 app
  targets, 4 shared schemes, 68 Swift files in each target.

Not checked: the SwiftUI screens (they need Apple's SDK, which only exists on a Mac), the generated Xcode project
opening in Xcode, running in the simulator or on an iPhone, signing. Expect to fix small compile errors the first time
it is built in Xcode.

Run the Core check yourself (Mac or Linux with Swift installed):

```
cd ios
./Tools/LinuxCheck/run.sh https://dav-dugda-sms.sites.tab.bot/api/v1/
```
