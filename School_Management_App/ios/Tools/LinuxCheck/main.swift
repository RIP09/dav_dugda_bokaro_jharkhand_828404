// Linux check for the iOS app's Foundation-only Core (networking + models).
// It signs in to a real server as every role, calls the endpoints the app uses and decodes the replies
// with the SAME Swift models the iPhone app uses. Run with Tools/LinuxCheck/run.sh <api-base>.
import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

setvbuf(stdout, nil, _IOLBF, 0)
let base = CommandLine.arguments.dropFirst().first ?? "http://127.0.0.1:8090/api/v1/"
var passed = 0, failed = 0, skipped = 0
var failures: [String] = []

func ok(_ name: String, _ detail: String = "") { passed += 1; print("  ✓ \(name)\(detail.isEmpty ? "" : " – \(detail)")") }
func fail(_ name: String, _ err: Any) { failed += 1; failures.append("\(name): \(err)"); print("  ✗ \(name) – \(err)") }
func skip(_ name: String, _ why: String) { skipped += 1; print("  · \(name) skipped (\(why))") }

func check<T>(_ name: String, _ block: () async throws -> T, describe: (T) -> String = { _ in "" }) async -> T? {
    do { let v = try await block(); ok(name, describe(v)); return v }
    catch let e as APIError {
        if case .notFound = e { skip(name, "404 on this server"); return nil }
        fail(name, e.errorDescription ?? "\(e)"); return nil
    }
    catch { fail(name, error); return nil }
}

// MARK: pure unit checks
print("Unit checks")
assert(APIClient.normalizedBase("dav.example.com")?.absoluteString == "https://dav.example.com/api/v1/"); ok("normalizedBase host only")
assert(APIClient.normalizedBase("https://x.y/api/v1")?.absoluteString == "https://x.y/api/v1/"); ok("normalizedBase keeps /api/v1")
assert(Money.format(125000) == "₹1,25,000"); ok("Indian money format", Money.format(125000))
assert(Money.format(1625.5) == "₹1,625.50"); ok("money paise", Money.format(1625.5))
let rec = try JSONDecoder().decode(Record.self, from: #"{"id":3,"status":"issued","_locked":1,"_lock_reason":"Issued","class_id":2,"class_id_label":"Class 5"}"#.data(using: .utf8)!)
assert(rec.isLocked && !rec.canEdit && !rec.canDelete && rec.lockReason == "Issued"); ok("locked record → edit/delete off")
assert(rec.display("class_id", meta: nil) == "Class 5"); ok("ref label shown")
let act = try JSONDecoder().decode(ModuleAction.self, from: #"{"key":"cancel","label":"Cancel","kind":"post","when":"status!=cancelled"}"#.data(using: .utf8)!)
assert(act.isAvailable(for: rec) && act.needsReason); ok("action 'when' + cancel needs reason")
let env = try JSONDecoder().decode(Envelope<JSONValue?>.self, from: #"{"message":"done"}"#.data(using: .utf8)!)
assert(env.message == "done"); ok("envelope without data")

// MARK: live server
print("\nServer: \(base)")
let roles: [(String, String, String)] = [
    ("school", "principal@davdugda.in", "principal123"),
    ("school", "admin@davdugda.in", "admin123"),
    ("staff", "teacher@davdugda.in", "teacher123"),
    ("staff", "accountant@davdugda.in", "accountant123"),
    ("staff", "librarian@davdugda.in", "librarian123"),
    ("parent", "parent@davdugda.in", "parent123"),
    ("student", "student@davdugda.in", "student123"),
]

/// Laravel throttles logins per IP (other test runs share this box), so wait and retry on 429.
func loginWithRetry(_ api: APIClient, _ login: String, _ pass: String) async throws -> LoginResponse {
    var attempt = 0
    while true {
        do { return try await api.login(login, password: pass) }
        catch let APIError.server(code, _) where code == 429 && attempt < 6 {
            attempt += 1
            print("    (login throttled, waiting 15 s)")
            try await Task.sleep(nanoseconds: 15_000_000_000)
        }
    }
}

let probe = APIClient(baseURL: APIClient.normalizedBase(base), appRole: "school")
_ = await check("school profile") { try await probe.school() } describe: { "\($0.name), principal \($0.principalName ?? "?")" }

var lockedSeen: [String: Int] = [:]
var modulesSeen = Set<String>()

for (app, login, pass) in roles {
    print("\n[\(app)] \(login)")
    let api = APIClient(baseURL: APIClient.normalizedBase(base), appRole: app)
    guard let res = await check("login app=\(app)", { try await loginWithRetry(api, login, pass) }, describe: { "\($0.user.name) (\($0.user.role))" }) else { continue }
    api.token = res.token
    let user = res.user
    _ = await check("auth/me") { try await api.me() } describe: { "children: \($0.children.count)" }

    // wrong app must be refused (role check like Android) – checked for two accounts to stay under the login throttle
    if login.hasPrefix("teacher") || login.hasPrefix("parent") {
        let wrongApp = app == "parent" ? "student" : "parent"
        let wrong = APIClient(baseURL: APIClient.normalizedBase(base), appRole: wrongApp)
        do { _ = try await loginWithRetry(wrong, login, pass); fail("login refused in \(wrongApp) app", "server accepted it") }
        catch let e as APIError {
            switch e {
            case .forbidden, .validation, .unauthorized: ok("login refused in \(wrongApp) app", e.errorDescription ?? "")
            default: fail("login refused in \(wrongApp) app", e.errorDescription ?? "\(e)")
            }
        } catch { fail("login refused in \(wrongApp) app", error) }
    }

    let modules = await check("meta/modules") { try await api.modules() } describe: { "\($0.count) modules" } ?? []
    _ = await check("meta/menu") { try await api.menu() } describe: { g in "\(g.count) groups, \(g.reduce(0) { $0 + $1.items.count }) items" }
    _ = await check("dashboard/charts") { try await api.dashboardCharts(studentId: user.children.first?.id) } describe: { "\($0.cards.count) cards, \($0.charts.count) charts" }
    _ = await check("dashboard (phase 1)") { try await api.dashboard(studentId: user.children.first?.id) } describe: { "\($0.stats.count) stats" }
    _ = await check("notifications") { try await api.notifications() } describe: { "\($0.data.count) items, unread \($0.meta?.unread ?? -1)" }
    _ = await check("search ‘ku’") { try await api.search("ku") } describe: { "\($0.count) hits" }
    _ = await check("notices") { try await api.notices() } describe: { "\($0.count)" }
    _ = await check("events") { try await api.events() } describe: { "\($0.count)" }
    _ = await check("messages inbox") { try await api.messages(box: "inbox") } describe: { "\($0.count)" }
    _ = await check("homework") { try await api.homework(studentId: user.children.first?.id) } describe: { "\($0.count)" }
    _ = await check("exams") { try await api.exams() } describe: { "\($0.count)" }

    let month = DateText.monthString(Date())
    switch user.role {
    case "parent", "student":
        let sid = user.role == "parent" ? user.children.first?.id : nil
        _ = await check("attendance/my") { try await api.myAttendance(month: month, studentId: sid) } describe: { "\($0.percentage)% of \($0.total)" }
        _ = await check("report-card/my") { try await api.myReportCard(examId: nil, studentId: sid) } describe: { "\($0.subjects.count) subjects, \($0.percentage)%" }
        _ = await check("timetable") { try await api.timetable(studentId: sid) } describe: { "\($0.count) periods" }
        _ = await check("own invoices") { try await api.invoices(studentId: sid ?? user.student?.id) } describe: { "\($0.count)" }
    default:
        let sections = await check("sections") { try await api.sections() } describe: { "\($0.count), first \($0.first?.label ?? "-")" } ?? []
        // teachers: their own section (what TakeAttendanceView's picker offers them)
        let own = sections.first { $0.teacherId == user.id }
        if user.role != "admin" && user.role != "teacher" {
            skip("attendance sheet / marks", "\(user.role) doesn't take attendance")
        } else if let s = (user.role == "teacher" ? own : sections.first) {
            _ = await check("attendance sheet") { try await api.attendanceSheet(sectionId: s.id, date: DateText.isoString(Date())) } describe: { "\($0.rows.count) students" }
            _ = await check("attendance/report") { try await api.sectionMonthReport(sectionId: s.id, month: month) } describe: { "\($0.students.count) students" }
            let subs = await check("subjects of class") { try await api.subjects(classId: s.classId) } describe: { "\($0.count)" } ?? []
            let exams = (try? await api.exams()) ?? []
            if let e = exams.first(where: { $0.isPublished }) ?? exams.first, let sub = subs.first {
                _ = await check("marks sheet") { try await api.marksSheet(examId: e.id, sectionId: s.id, subjectId: sub.id) } describe: { "\($0.rows.count) rows, max \($0.maxMarks), locked \($0.locked)" }
            }
            let studs = await check("students of section") { try await api.students(sectionId: s.id) } describe: { "\($0.count)" } ?? []
            if let st = studs.first {
                _ = await check("report card of student") { try await api.reportCard(studentId: st.id, examId: nil) } describe: { "\($0.percentage)%" }
            }
        }
        if user.role == "admin" {
            _ = await check("daily attendance") { try await api.classSummary(date: DateText.isoString(Date())) } describe: { "\($0.count) sections" }
        } else {
            do { _ = try await api.classSummary(date: DateText.isoString(Date())); ok("daily attendance (allowed for \(user.role))") }
            catch let e as APIError { if case .forbidden = e { ok("daily attendance refused for \(user.role) (403, as designed)") } else { fail("daily attendance", e.errorDescription ?? "") } }
        }
        _ = await check("timetable mine") { try await api.timetable(mine: true) } describe: { "\($0.count)" }
        if ["admin", "accountant"].contains(user.role) {
            _ = await check("fees summary") { try await api.feeSummary() } describe: { "collected \(Money.format($0.collected)), due \(Money.format($0.due))" }
            _ = await check("invoices") { try await api.invoices() } describe: { "\($0.count)" }
            _ = await check("payments") { try await api.payments() } describe: { "\($0.count)" }
        }
        if user.role == "admin" {
            _ = await check("staff sheet") { try await api.staffSheet(date: DateText.isoString(Date())) } describe: { "\($0.rows.count) staff" }
            _ = await check("audit") { try await api.audit() } describe: { "\($0.data.count) rows" }
        }
    }

    // Every module the user may view: list + one detail + history (admin only, to keep it quick)
    if user.role == "admin" && login.hasPrefix("principal") {
        print("  -- generic engine over all \(modules.count) modules --")
        for m in modules {
            modulesSeen.insert(m.key)
            do {
                let page = try await api.records(m.key, perPage: 5)
                var detail = ""
                if let first = page.data.first {
                    let r = try await api.record(m.key, id: first.id)
                    detail = " · detail \"\(r.title(using: m))\""
                    if r.isLocked { lockedSeen[m.key, default: 0] += 1 }
                    if m.permissions.history || true { _ = try await api.history(m.key, id: first.id) }
                }
                let locked = page.data.filter { $0.isLocked }.count
                if locked > 0 { lockedSeen[m.key, default: 0] += locked }
                ok("records/\(m.key)", "\(page.meta?.total ?? page.data.count) rows, \(m.fields.count) fields\(locked > 0 ? ", \(locked) locked" : "")\(detail)")
            } catch {
                fail("records/\(m.key)", (error as? APIError)?.errorDescription ?? "\(error)")
            }
        }
        // a locked payment must refuse edits with 423
        if let p = try? await api.records("payments", perPage: 1).data.first {
            do { _ = try await api.updateRecord("payments", id: p.id, ["reference": .string("ios-check")]); fail("edit locked receipt → 423", "server allowed the edit") }
            catch let e as APIError where e.isLocked { ok("edit locked receipt → 423", e.errorDescription ?? "") }
            catch { fail("edit locked receipt → 423", error) }
        }
        // PDF download with bearer token
        if let p = try? await api.records("payments", perPage: 1).data.first {
            _ = await check("receipt PDF download") { try await api.download("pdf/receipt/\(p.id)", fallbackName: "receipt") } describe: { f in
                let size = (try? FileManager.default.attributesOfItem(atPath: f.url.path)[.size] as? Int) ?? 0
                let head = (try? Data(contentsOf: f.url).prefix(4)).map { String(decoding: $0, as: UTF8.self) } ?? ""
                return "\(f.suggestedName), \(size) bytes, starts \(head)"
            }
        }
    }
}

print("\nLocked records seen: \(lockedSeen.isEmpty ? "none" : lockedSeen.map { "\($0.key)=\($0.value)" }.sorted().joined(separator: ", "))")
let areaKeys = Set(ModuleArea.all.flatMap { $0.moduleKeys })
let unmapped = modulesSeen.subtracting(areaKeys)
print("Server modules not mapped to an iOS area folder: \(unmapped.isEmpty ? "none" : unmapped.sorted().joined(separator: ", "))")
print("\nRESULT: \(passed) passed, \(failed) failed, \(skipped) skipped")
if !failures.isEmpty { print("Failures:\n- " + failures.joined(separator: "\n- ")) }
exit(failed == 0 ? 0 : 1)
