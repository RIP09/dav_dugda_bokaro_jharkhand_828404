# DAV School Management 2.0.1: class shown with every section

**Reported by Deepanshu on 27 Oct 2026:** *"when I adding students and choosing sections gets difficult because of not written for which class this section appears."*

## What was wrong (checked in the 2.0.0 apps before the fix)

| Where | 2.0.0 behaviour |
|---|---|
| Android (all 4 apps), any form built from the server's module list (Students → Add/Edit, Timetable periods, Homework, Marks, Attendance records, Promotions, Syllabus, Study material, Date sheet, Enquiries) | The **Section** picker listed `A, A, A, A, A, A, A, A, B, A, B …`: one entry for every section in the school, with no class. It stayed that way even after a class was chosen. **Subject** pickers listed bare `English`, `Hindi` … once per class. |
| iOS (source) | Same as Android. The ref picker used the server's `label_field` (`name`). |
| Windows | Section options carried the class (`Class 6-A`), but the list was not filtered. After choosing Class 6 you could still pick `Class 7-A`, and the server accepted that mismatch for timetable, homework and marks. |
| Server | `meta/modules` told every app to label sections and subjects by `name` only. Only Students checked that a section belonged to the chosen class. |
| Student picker | Showed only the name, so two students with the same name looked identical. Teacher, parent and exam pickers showed no department, phone or session either. |
| Android menu | Entries the server sends as `module:timetable` and `module:staff_attendance` opened "This section is not available" (found by the contract test). |

## What changed

- **Server (backend/):**
  - Every record row now carries `_option`, the text to show in a picker: `Class 6 – A`, `Class 11 – A (Science)`, `Mathematics (Class 6)`, `Megha Singh · Class 8 – A · DAV20260001`, `Sunil Kumar Mishra · Mathematics`, `Rajesh Kumar Mahato · 9431000001` and `Periodic Test 1 (2026-27)`.
  - `meta/modules` now sends `ref.label_field = "_option"`, so the **already-installed 2.0.0 apps** show these labels as soon as the server is updated. Tested by running the unmodified 2.0.0 Android app against the new server.
  - `meta/modules` also sends `ref.depends_on` (Section ← Class, Subject ← Class, Student ← Section/Class, and From/To section ← From/To class).
  - The server refuses a section, subject or student that does not belong to the chosen class in every module, with a message like "“Class 7 – A” does not belong to the chosen class."
  - `GET sections` and `GET classes` return `label` and `class_name` on each section. Attendance, timetable and homework `class_label` now use the same `Class 6 – A` form.
  - New optional **Stream** on sections, for Class XI–XII only: **Science** or **Commerce**. Humanities/Arts is not offered, and the server rejects any other value or a stream on Nursery–X. This needs migration `2026_10_30_100000_add_stream_to_sections`.
  - Excel/CSV import accepts `Class 8 – A`, `Class 8 - A`, `Class 8-A` and `Class 11 – B (Commerce)`.
  - Demo data: Class XI and XII now have A (Science) and B (Commerce), and Accountancy, Business Studies and Economics were added.
- **Android 2.0.1 (versionCode 4):**
  - The Section picker waits ("Section * — choose Class first"). After a class is chosen it lists only that class's sections, labelled `Class 6 – A`.
  - Changing the class clears a section from another class. If a class has only one section, it is picked automatically.
  - The same rules apply to the Subject and Student pickers and to the list filters.
  - The Class/Section pickers on the Attendance, Marks, Fees, Homework, Reports and Timetable screens label sections with their class.
  - Adding a section to Class XI/XII offers the Science/Commerce stream.
  - `module:` menu entries now open their module.
  - Old servers still get class labels, built from `class_id_label`.
- **Windows 2.0.1:** The generic forms and list filters filter Section/Subject/Student by the chosen class ("Choose Class first"). The shared Class/Section picker shows `Class 6 – A`.
- **iOS (source only; not compiled, because this was built on Linux with no Xcode):** The ref picker shows `_option`, waits for the parent ("Choose Class first"), filters by it and clears the child when the class changes. `MARKETING_VERSION` is now 2.0.1.

## Tests run (30 Oct 2026, on Linux)

- `php artisan test`: 18 passed, including the new `ClassSectionLabelTest` (6 tests).
- `tests/smoke_api.py` against a fresh seed: 138/138.
- Android Robolectric (JVM, not a phone or emulator), all 4 flavors:
  - `AppFlowTest` ×4 passed.
  - New `SectionPickerTest` passed on School Admin. It walks Students → Add → Section → Class 6 → Section and asserts the options are only `Class 6 – A`, `Class 6 – B`.
  - Core tests passed: `ApiContractTest` 5, `ApiV2ContractTest` 1 (fixtures re-captured from the new server), and the new `PickerLabelTest` 6.
- Windows renderer: the built bundle was walked in headless Chromium against the new server (Students → Add → Section). The Electron shell itself was not launched.

Screenshots: `docs/screenshots/section_labels/`.
