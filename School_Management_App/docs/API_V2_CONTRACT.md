# API v2 contract — generic modules, editing, locking, PDFs (Phase 2)

This is the contract every management client (Windows app, Android, iOS) codes against.
Base URL: `<server>/api/v1/` · JSON · `Authorization: Bearer <token>` · envelope `{"data": ..., "message"?: "..."}` ·
errors `{"message": "...", "errors"?: {field: [msg]}}` with HTTP 401/403/404/422, and **423 Locked** for locked records.

All Phase-1 endpoints (README §3 "API overview") keep working unchanged.

## Auth
`POST auth/login {login, password, app}` — `app` ∈ `school|staff|parent|student|web`.
`web` (Windows app, iOS app) accepts **every** role. Response `{data:{token, user:{id,name,email,phone,role,photo_url,...,children?[]}}}`.
`GET auth/me` · `POST auth/logout` · `POST auth/change-password {current_password,new_password}`.

Roles: `admin` (Principal / office — demo principal **Prasanjeet Kumar Paul**, `principal@davdugda.in` / `principal123`;
`admin@davdugda.in` / `admin123` still works), `teacher`, `accountant`, `librarian`, `staff`, `parent`, `student`.

## School profile (public, no auth)
`GET school` → `{name, short_name, address, phone, email, affiliation_no, school_code, principal_name, logo_url, current_session, board}`

## Module registry — drives every generic screen
`GET meta/modules` → modules the signed-in user can at least view:
```json
{"data":[{
  "key":"enquiries", "title":"Admission Enquiries", "group":"Admissions", "icon":"person_add",
  "label_field":"name",                // field to show as the row title
  "subtitle_fields":["phone","class_name"],
  "permissions":{"view":true,"create":true,"update":true,"delete":true,"export":true,"import":false},
  "lockable":false,                    // true → rows may come back with _locked=true
  "lock_note":"Issued certificates cannot be edited; cancel and re-issue.",   // optional, explain the rule
  "fields":[{
     "name":"name","label":"Student name","type":"text",
     "required":true,"list":true,"filter":false,"readonly":false,"immutable":false,
     "options":null,                   // for type select: [{"value":"new","label":"New"}]
     "ref":null,                       // for type ref: {"module":"classes","label_field":"name"}
     "help":null, "default":null
  }],
  "actions":[{"key":"convert","label":"Convert to admission","kind":"post","confirm":"Create student record?","when":"status!=admitted"},
             {"key":"pdf","label":"Print","kind":"pdf"}]
}]}
```
Field `type` ∈ `text, textarea, number, money, date, time, datetime, select, bool, ref, email, phone, file, image, json(readonly)`.
`readonly` = never sent by forms (computed/server-set). `immutable` = may be set on create, never changed after (e.g. admission_no, receipt_no).
`filter:true` = offer as a list filter (`filter[<name>]=value`). `ref` fields come back as id + `<name>_label`.
`file`/`image` fields come back as stored path + `<name>_url` (absolute URL); upload first via `POST uploads`, then send the returned `path`.

`GET meta/menu` → the ordered menu for this user: `[{group, items:[{key,title,icon,screen}]}]` where `screen` is either a
module key (generic list/form) or a custom screen id: `dashboard, attendance_take, attendance_report, staff_attendance,
timetable, marks_entry, report_cards, fee_collect, fee_reminders, payroll_run, promotion, import, backup, audit, settings, messages, notifications, calendar, search, profile`.

## Generic records (every module)
- `GET records/{module}?q=&page=1&per_page=25&sort=-id&filter[field]=value` →
  `{"data":[row...], "meta":{"total":120,"page":1,"per_page":25,"last_page":5}}`
- `GET records/{module}/{id}` → `{"data": row}`
- `POST records/{module}` (JSON of fields) → 201 `{"data": row}`
- `PUT records/{module}/{id}` (only the changed fields are fine) → `{"data": row}` · **423** when locked · 422 when an immutable field changes
- `DELETE records/{module}/{id}` → `{"data":null}` · **423** when locked
- `GET records/{module}/{id}/history` → audit trail of that record (same shape as `audit` rows)
- `POST records/{module}/{id}/actions/{action}` (body optional, e.g. `{"reason":"wrong amount"}`) → `{"data": row|object, "message"}`

Each row: its fields + `id`, `_title` (label), `_subtitle`, `_locked` (bool), `_lock_reason` (string|null),
`_can_edit`, `_can_delete` (bool), `created_at`, `updated_at`.

### Locked / permanent records (server-enforced, 423 on edit/delete)
| Module | Rule |
|---|---|
| `payments` (fee receipts) | always locked once issued; correction = action `cancel` (reason required) which marks it cancelled and reverses the invoice; then collect again |
| `certificates` (TC, character, bonafide …) | locked once status=`issued`; action `cancel` |
| `marks`, `exams` | locked when the exam's results are locked (action `lock_results` on exam, admin only; `unlock_results` not offered) |
| `payslips` | locked once status=`paid` |
| `promotions` (roll/class history) | always locked |
| `audit` | read-only, never editable or deletable |
| `students.admission_no`, `staff.employee_id`, `invoices.invoice_no`, `payments.receipt_no`, `certificates.certificate_no` | immutable fields |

## Module keys (initial set; always read meta/modules)
Admissions: `enquiries`, `admissions` · People: `students`, `student_documents`, `parents`, `teachers` (role teacher), `staff` (all staff incl. non-teaching) · Academic: `academic_years`,
`classes`, `sections`, `subjects`, `timetable`, `syllabus`, `study_materials`, `homework` · Attendance: `student_attendance`,
`staff_attendance`, `leaves` · Exams: `exams`, `exam_schedules`, `marks`, `grades` · Certificates: `certificates`, `promotions` ·
Fees: `fee_types`, `fee_structures`, `concessions`, `invoices`, `payments` · Accounts: `account_heads`, `transactions` ·
Payroll: `salary_structures`, `payslips` · Inventory: `inventory_items`, `stock_movements`, `assets` · Hostel: `hostels`,
`hostel_rooms`, `hostel_allocations` · Transport: `transport_routes`, `vehicles` · Library: `books`, `book_issues` ·
Front office: `visitors`, `postal`, `call_logs` · Communication: `notices`, `events`, `complaints` · System: `settings`, `audit`.

## Custom endpoints
- `GET dashboard/charts` → `{"cards":[{label,value,icon,color}], "charts":[{"id","title","type":"bar|line|pie|doughnut","labels":[...],"datasets":[{"label","data":[...]}]}]}` (role-aware)
- `GET search?q=` → `{"data":[{module,id,title,subtitle,screen}]}` (students, staff, parents, books, invoices, receipts …; role-scoped)
- `GET notifications` → `{data:[{id,title,body,module,record_id,read_at,created_at}], meta:{unread}}` · `POST notifications/{id}/read` · `POST notifications/read-all`
- `POST notices/{id}/read` (marks read) · `GET notices/{id}/reads` (admin/teacher: who read it, counts)
- `GET attendance/report?section_id=&month=YYYY-MM` → per student day-by-day + totals · `GET attendance/daily?date=` → per section counts
- `POST fees/reminders {class_id?, section_id?}` → creates in-app notifications for parents with dues; returns list incl. `whatsapp_url` (free wa.me link) per parent
- `POST fees/generate {class_id?, month:"YYYY-MM"}` → invoices from fee structures minus concessions (+ transport/hostel fee)
- `POST payroll/run {month:"YYYY-MM"}` → draft payslips from salary structures · action `pay` on payslip
- `POST promotion/run {from_academic_year_id, to_academic_year_id, mappings:[{from_class_id,to_class_id}], exclude_student_ids:[]}`
- `POST uploads` (multipart `file`, optional `folder`) → `{"data":{"path":"uploads/x.jpg","url":"https://.../storage/uploads/x.jpg"}}`
- `POST students/{id}/photo` (multipart `photo`) → updated student

### Files: PDFs, export, import, backup (all GET return files; pass `?token=<bearer>` to open in a browser/WebView/Electron)
- `GET pdf/{doc}/{id}` — doc ∈ `receipt` (payment id), `invoice`, `report-card` (student id, `?exam_id=`), `id-card` (student id),
  `staff-id-card` (user id), `admit-card` (student id, `?exam_id=`), `certificate` (certificate id — TC/character/bonafide),
  `salary-slip` (payslip id), `attendance` (section id, `?month=`), `fee-statement` (student id)
- `GET pdf/id-cards?section_id=` (all cards of a section) · `GET pdf/admit-cards?exam_id=&section_id=`
- `GET export/{module}?format=xlsx|csv|pdf&filter[...]` (same filters as records)
- `GET import/{module}/template?format=xlsx|csv` · `POST import/{module}` (multipart `file`, `dry_run=1` to preview) →
  `{"data":{"created":n,"updated":n,"errors":[{"row":3,"message":"..."}]}}` — modules: students, staff, parents, books, inventory_items, fee_structures, transactions, visitors
- `GET backup/download` (admin) → zip with every table as JSON + CSV and the SQLite file when used · `POST backup/restore` is not offered from apps (server CLI only)
- `GET audit?module=&user_id=&action=&from=&to=&page=` (admin) → `{data:[{id,user_id,user_name,action,module,record_id,record_title,changes:{field:{old,new}},ip,created_at}], meta}`

Every PDF shows the school header (name, address, affiliation no., logo) and is signed by the Principal **Prasanjeet Kumar Paul**
(from setting `principal_name`).
