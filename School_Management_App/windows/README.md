# DAV School Management — Windows PC software

The full school management system for **DAV Public School, Dugda** (Bokaro, Jharkhand 828404) as a Windows desktop app,
for the principal (**Prasanjeet Kumar Paul**), office, accountant, librarian and teachers.
It talks to the same school server as the Android and iOS apps, so everything stays in sync.

**Version 2.0.0** · Windows 10 / 11 (64-bit) · free and open source (Electron, Vue 3, Chart.js)

## Download

From the `releases/` folder of this repository:

| File | What it is |
|---|---|
| `DAV_School_Management_Setup_2.0.0.exe` | Installer — installs for the current user, adds Desktop + Start-menu shortcuts |
| `DAV_School_Management_Portable_2.0.0.zip` | Portable — unzip anywhere (even a pen drive) and run `DAV School Management.exe`, nothing to install |

## Install

1. Double-click `DAV_School_Management_Setup_2.0.0.exe`.
2. Windows may show **"Windows protected your PC"** (SmartScreen). This appears because the app is new and not
   code-signed (a signing certificate costs money). Click **More info → Run anyway**.
3. Choose the folder (or keep the default) → **Install**. The app opens when it finishes.

Uninstall any time from *Settings → Apps → DAV School Management*. Your school data is on the server, not on the PC,
so uninstalling never deletes school data.

## First start

1. **Server address** screen: type your school server address (for example `https://davdugda.alwaysdata.net`)
   and press **Test connection**, or press **Use demo server** to try it with demo data.
   You can change it later: *File → Server settings*.
2. **Sign in** with your school login (email or mobile number + password).

Demo logins (demo server only):

| Who | Login | Password |
|---|---|---|
| Principal (Prasanjeet Kumar Paul) | `principal@davdugda.in` | `principal123` |
| Office admin | `admin@davdugda.in` | `admin123` |
| Teacher | `teacher@davdugda.in` | `teacher123` |
| Accountant | `accountant@davdugda.in` | `accountant123` |
| Librarian | `librarian@davdugda.in` | `librarian123` |
| Parent / Student | `parent@davdugda.in` / `student@davdugda.in` | `parent123` / `student123` |

Each person sees only the menu their role allows (the server decides).

## What's inside

- **Dashboard** with live counts and charts, today's notices and events, quick actions, Principal's name.
- **Every module** the server offers, each with a list (search, filters, sort, pages), Add / Edit / Delete,
  **Export** to Excel / CSV / PDF and **Import** from Excel where allowed:
  Admissions & enquiries, Students (photo, documents), Parents, Teachers, Staff, Academics (sessions, classes, sections,
  subjects, syllabus, study material), Attendance, Leave, Timetable, Homework, Exams (schedules, marks, grades),
  Certificates (TC, character, bonafide), Promotions, Fees (types, structures, concessions, invoices, receipts),
  Accounts (income/expense), Payroll (salary structures, slips), Inventory & assets, Hostel, Transport, Library,
  Front office (visitors, postal, calls), Notices (with who-read-it), Events, Complaints, Audit log.
- **Editing with history**: every change is saved with who/when/old → new. The principal can open the **History** tab
  of any record.
- **Locked (permanent) records** show a 🔒 badge with the reason and cannot be edited or deleted:
  issued fee receipts (correct with *Cancel receipt* + reason, then collect again), issued certificates,
  results after *Lock results*, paid salary slips, promotion history, the audit log.
- **Special screens**: Take attendance (mark-all-present grid), Attendance report (month grid → PDF/Excel),
  Staff attendance, Timetable (week view, clash check), Marks entry (grades worked out live), Report cards,
  Collect fees (find student → dues → receive → print receipt), Fee reminders (in-app + free WhatsApp links),
  Generate monthly fees, Payroll run, Promotion / new-session wizard, **Import from Excel wizard**
  (template → upload → check → import, to move paper registers in), Backup download, Audit log, School settings
  (name, logo, principal, session), Messages, Notifications, Academic calendar, Global search (**Ctrl+K**), Profile & password.
- **Desktop extras**: remembers window size/position, only one copy runs at a time, PDFs/exports/backups are saved to your
  *Downloads* folder and opened (or *Save as…*), print preview for receipts and certificates, friendly "can't reach the
  server" screen with **Retry**, links (WhatsApp, websites) open in your normal browser, update check against this
  GitHub repository (*Help → Check for updates*).

Keyboard: **Ctrl+K** search · **Ctrl+P** print page · **Ctrl+R / F5** reload · **F11** full screen · **Ctrl+D** dashboard · **Ctrl + / Ctrl −** zoom.

## Build it yourself (any PC with Node.js 20+)

```bash
cd School_Management_App/windows
npm ci
npm run dist:win        # → dist/DAV_School_Management_Setup_2.0.0.exe and dist/DAV_School_Management_Portable_2.0.0.zip
npm start               # run it on your own computer for testing
```

Building the Windows installer works on Windows, and on Linux/macOS too (electron-builder; on Linux `wine` is used to put
the icon into the .exe). To make the app open a different server by default, set it on first start, or run it once with
the environment variable `DAV_SERVER_URL=https://your-server`.

## Source layout (one folder per module, same names as backend / Android / iOS)

```
windows/
├── src/main/                     Electron main process
│   ├── index.js
│   └── modules/{window, server-config, menu, downloads, printing, offline, about, updates}/
├── src/preload/index.js          safe bridge to the UI
├── renderer/                     Vue 3 management UI (Vite)
│   └── src/
│       ├── api/                  server client (token, errors, 423 Locked)
│       ├── layout/               sidebar, top bar, search palette, offline screen
│       ├── components/           shared UI (modal, drawer, charts, stat cards, pickers)
│       └── modules/
│           ├── generic/          the module engine: list, form, record drawer, history, actions, lock badges
│           ├── dashboard/  students/  parents/  teachers/  staff/  admissions/  academics/
│           ├── attendance/ leave/  timetable/  homework/  exams/  certificates/  fees/  accounts/
│           ├── payroll/  inventory/  hostel/  transport/  library/  front_office/  communication/
│           └── reports/  settings/  audit/  profile/
├── build/                        icon (png + ico)
└── electron-builder.yml          installer + portable zip settings
```

Every module shown comes from the server's module registry (`GET meta/modules`, `GET meta/menu`), so new modules added
on the server appear in the app automatically. API contract: `../docs/API_V2_CONTRACT.md`.
