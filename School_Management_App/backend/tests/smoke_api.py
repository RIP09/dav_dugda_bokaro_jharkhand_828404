#!/usr/bin/env python3
"""End-to-end smoke test of the DAV School API against a freshly seeded database.
Usage:  php artisan migrate:fresh --seed && php artisan serve &   then   python3 tests/smoke_api.py [base_url]
"""
import sys, json, datetime, urllib.request, urllib.error

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8000/api/v1").rstrip("/") + "/"
passed, failed = 0, []

def call(method, path, token=None, body=None):
    req = urllib.request.Request(BASE + path, method=method, data=json.dumps(body).encode() if body is not None else None)
    req.add_header('User-Agent', 'DAV-smoke-test/2.0')  # some CDNs block the default Python-urllib agent
    req.add_header("Accept", "application/json"); req.add_header("Content-Type", "application/json")
    if token: req.add_header("Authorization", "Bearer " + token)
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            return r.status, json.loads(r.read() or b"null")
    except urllib.error.HTTPError as e:
        raw = e.read()
        try: return e.code, json.loads(raw)
        except Exception: return e.code, {"raw": raw[:300].decode(errors="replace")}

def check(name, cond, info=""):
    global passed
    if cond: passed += 1; print("  ok  ", name)
    else: failed.append(name); print("  FAIL", name, str(info)[:400])

def expect(name, method, path, token=None, body=None, status=200):
    code, js = call(method, path, token, body)
    check(f"{method} {path} -> {status}  [{name}]", code == status, (code, js))
    return js.get("data") if isinstance(js, dict) else None

today = datetime.date.today().isoformat()

def login(login_id, pw, app):
    code, js = call("POST", "auth/login", body={"login": login_id, "password": pw, "app": app})
    check(f"login {login_id} into {app} app", code == 200 and js["data"]["token"], js)
    return js["data"]["token"], js["data"]["user"]

print("== public")
expect("ping", "GET", "ping")
expect("school profile", "GET", "school")
code, js = call("POST", "auth/login", body={"login": "admin@davdugda.in", "password": "wrong", "app": "school"})
check("wrong password rejected (401)", code == 401, js)
code, js = call("POST", "auth/login", body={"login": "parent@davdugda.in", "password": "parent123", "app": "school"})
check("parent cannot sign in to School app (403)", code == 403, js)
code, js = call("GET", "dashboard")
check("no token -> 401", code == 401, js)

print("== school admin")
A, admin = login("admin@davdugda.in", "admin123", "school")
d = expect("dashboard", "GET", "dashboard", A); check("admin dashboard has stats", d and len(d["stats"]) >= 6, d)
classes = expect("classes", "GET", "classes", A); check("14 classes seeded", len(classes) == 14, len(classes or []))
c8 = next(c for c in classes if c["name"] == "Class 8"); sec8a = next(s for s in c8["sections"] if s["name"] == "A")
expect("sections", "GET", "sections?class_id=%d" % c8["id"], A)
subs = expect("subjects", "GET", "subjects?class_id=%d" % c8["id"], A)
studs = expect("students by section", "GET", "students?section_id=%d" % sec8a["id"], A); check("students listed", len(studs) > 0)
expect("student search", "GET", "students?search=Aarav", A)
expect("student detail", "GET", "students/%d" % studs[0]["id"], A)
new = expect("admit student + new parent", "POST", "students", A, {"name": "Test Kumar", "class_id": c8["id"], "section_id": sec8a["id"], "gender": "male",
      "parent_name": "Test Parent", "parent_phone": "9000099999", "father_name": "Test Parent"}, 201)
check("admission no generated", new and new["admission_no"].startswith("DAV"), new)
expect("update student", "PUT", "students/%d" % new["id"], A, {"roll_no": 99, "blood_group": "O+"})
code, js = call("POST", "auth/login", body={"login": "9000099999", "password": "parent123", "app": "parent"})
check("new parent can sign in with phone", code == 200, js)
t = expect("create teacher", "POST", "users", A, {"name": "New Teacher", "email": "new.teacher@davdugda.in", "role": "teacher", "designation": "TGT"}, 201)
expect("list staff", "GET", "users?role=staff_all", A)
expect("list parents", "GET", "users?role=parent", A)
expect("toggle user", "POST", "users/%d/toggle-active" % t["id"], A)
expect("toggle user back", "POST", "users/%d/toggle-active" % t["id"], A)
cl = expect("create class", "POST", "classes", A, {"name": "Nursery", "sections": ["A", "B"]}, 201)
expect("add section", "POST", "sections", A, {"class_id": cl["id"], "name": "C"}, 201)
sj = expect("add subject", "POST", "subjects", A, {"class_id": cl["id"], "name": "Rhymes", "teacher_id": t["id"]}, 201)
expect("academic years", "GET", "academic-years", A)
expect("update school settings", "PUT", "school", A, {"principal": "Dr. R. K. Sinha"})
expect("class attendance summary", "GET", "attendance/class-summary", A)
sheet = expect("attendance sheet", "GET", "attendance/students?section_id=%d&date=%s" % (sec8a["id"], today), A)
recs = [{"student_id": r["student_id"], "status": "present" if i % 5 else "absent"} for i, r in enumerate(sheet["rows"])]
expect("save attendance", "POST", "attendance/students", A, {"section_id": sec8a["id"], "date": today, "records": recs})
sheet2 = expect("attendance sheet again", "GET", "attendance/students?section_id=%d&date=%s" % (sec8a["id"], today), A)
check("attendance persisted", sheet2["already_marked"] and all(r["status"] for r in sheet2["rows"]), sheet2)
ss = expect("staff attendance sheet", "GET", "attendance/staff?date=" + today, A)
expect("save staff attendance", "POST", "attendance/staff", A, {"date": today, "records": [{"user_id": r["user_id"], "status": "present"} for r in ss["rows"]]})
tt = expect("timetable for section", "GET", "timetable?section_id=%d" % sec8a["id"], A); check("timetable has 36 periods", len(tt) == 36, len(tt or []))
code, js = call("POST", "timetable", A, {"section_id": sec8a["id"], "subject_id": subs[0]["id"], "day": 1, "start_time": "08:10", "end_time": "08:30"})
check("timetable clash detected (422)", code == 422, js)
expect("add period", "POST", "timetable", A, {"section_id": sec8a["id"], "subject_id": subs[0]["id"], "day": 6, "start_time": "12:30", "end_time": "13:10"}, 201)
exams = expect("exams", "GET", "exams", A)
hy = next(e for e in exams if e["name"].startswith("Half"))
pt2 = next(e for e in exams if e["name"] == "Periodic Test 2")
expect("exam schedules", "GET", "exams/%d/schedules?class_id=%d" % (hy["id"], c8["id"]), A)
ms = expect("marks sheet", "GET", "marks?exam_id=%d&section_id=%d&subject_id=%d" % (pt2["id"], sec8a["id"], subs[0]["id"]), A)
expect("save marks", "POST", "marks", A, {"exam_id": pt2["id"], "section_id": sec8a["id"], "subject_id": subs[0]["id"], "max_marks": 40,
       "records": [{"student_id": r["student_id"], "marks_obtained": 30 + i % 10} for i, r in enumerate(ms["rows"])]})
code, js = call("POST", "marks", A, {"exam_id": pt2["id"], "section_id": sec8a["id"], "subject_id": subs[0]["id"], "max_marks": 40, "records": [{"student_id": ms["rows"][0]["student_id"], "marks_obtained": 55}]})
check("marks above max rejected (422)", code == 422, js)
rc = expect("report card", "GET", "students/%d/report-card?exam_id=%d" % (studs[0]["id"], hy["id"]), A)
check("report card computed", rc and rc["percentage"] > 0 and rc["grade"] and rc["rank"], rc)
expect("grades", "GET", "grades", A)
ex = expect("create exam", "POST", "exams", A, {"name": "Surprise Test", "term": "Term 2"}, 201)
expect("publish exam", "POST", "exams/%d/publish" % ex["id"], A)
expect("fee summary", "GET", "fees/summary", A)
expect("fee types", "GET", "fee-types", A)
inv = expect("bill whole section", "POST", "invoices", A, {"class_id": c8["id"], "section_id": sec8a["id"], "title": "Science Exhibition Fee", "amount": 200, "due_date": today}, 201)
check("invoices created for section", inv["count"] == len(sheet["rows"]), inv)
due = expect("due invoices", "GET", "invoices?status=due&student_id=%d" % studs[0]["id"], A)
one = due[0]
p = expect("record payment", "POST", "invoices/%d/payments" % one["id"], A, {"amount": one["balance"], "method": "upi", "reference": "UPI123"}, 201)
check("invoice marked paid", p["invoice"]["status"] == "paid", p)
code, js = call("POST", "invoices/%d/payments" % one["id"], A, {"amount": 10})
check("overpayment rejected (422)", code == 422, js)
expect("payments", "GET", "payments", A)
expect("post notice", "POST", "notices", A, {"title": "Test notice", "body": "Hello all", "audience": "all"}, 201)
expect("add event", "POST", "events", A, {"title": "Test event", "start_date": today}, 201)
bk = expect("add book", "POST", "library/books", A, {"title": "Test Book", "author": "Someone", "total_copies": 2}, 201)
iss = expect("issue book", "POST", "library/issues", A, {"book_id": bk["id"], "user_id": studs[1]["user_id"]}, 201)
expect("return book", "POST", "library/issues/%d/return" % iss["id"], A)
rt = expect("add route", "POST", "transport/routes", A, {"name": "Route 9 - Test", "fare": 500, "stops": ["A", "B"]}, 201)
expect("route students", "GET", "transport/routes/1/students", A)
leaves = expect("all leaves", "GET", "leaves?scope=review", A)
pend = [l for l in leaves if l["status"] == "pending" and l["student_id"]]
expect("approve student leave", "POST", "leaves/%d/review" % pend[0]["id"], A, {"status": "approved", "note": "ok"})
expect("contacts", "GET", "messages/contacts", A)
expect("promote section", "POST", "students/promote", A, {"from_section_id": cl["sections"][0]["id"], "to_class_id": cl["id"], "to_section_id": cl["sections"][1]["id"]})

print("== staff: teacher")
T, teacher = login("teacher@davdugda.in", "teacher123", "staff")
d = expect("teacher dashboard", "GET", "dashboard", T); check("teacher sees today's classes key", "today_classes" in d)
mytt = expect("my timetable", "GET", "timetable?mine=1", T); check("teacher timetable only own periods", all(e["teacher_id"] == teacher["id"] for e in mytt), mytt[:1])
my_sections = sorted({e["section_id"] for e in mytt})
sec = my_sections[0]
sh = expect("teacher attendance sheet", "GET", "attendance/students?section_id=%d&date=%s" % (sec, today), T)
expect("teacher saves attendance", "POST", "attendance/students", T, {"section_id": sec, "date": today, "records": [{"student_id": r["student_id"], "status": "present"} for r in sh["rows"]]})
others = [s["id"] for c in classes for s in c["sections"] if s["id"] not in my_sections and s["teacher_id"] != teacher["id"]]
if others:
    code, js = call("POST", "attendance/students", T, {"section_id": others[0], "date": today, "records": [{"student_id": studs[0]["id"], "status": "present"}]})
    check("teacher blocked from other class attendance (403)", code == 403, js)
hw = expect("post homework", "POST", "homework", T, {"section_id": sec8a["id"], "subject_id": subs[0]["id"], "title": "Smoke test homework", "description": "Do it", "due_date": today}, 201)
expect("my homework", "GET", "homework", T)
expect("teacher notice -> parents", "POST", "notices", T, {"title": "Class note", "body": "Bring colours", "audience": "all"}, 201)
expect("teacher applies leave", "POST", "leaves", T, {"from_date": today, "to_date": today, "reason": "Personal work", "type": "casual"}, 201)
expect("teacher own attendance", "GET", "attendance/my", T)
expect("teacher library", "GET", "library/issues", T)
code, js = call("POST", "users", T, {"name": "x", "email": "x@x.in", "role": "teacher"})
check("teacher cannot create users (403)", code == 403, js)
code, js = call("POST", "invoices", T, {"student_id": 1, "title": "x", "amount": 1})
check("teacher cannot bill (403)", code == 403, js)
expect("teacher messages", "GET", "messages", T)

print("== staff: accountant & librarian")
AC, _ = login("accountant@davdugda.in", "accountant123", "staff")
expect("accountant fee summary", "GET", "fees/summary", AC)
expect("accountant invoices", "GET", "invoices?status=due", AC)
L, _ = login("librarian@davdugda.in", "librarian123", "staff")
expect("librarian issues", "GET", "library/issues?status=issued", L)
expect("librarian adds book", "POST", "library/books", L, {"title": "Librarian Book"}, 201)
code, js = call("POST", "invoices", L, {"student_id": 1, "title": "x", "amount": 1})
check("librarian cannot bill (403)", code == 403, js)

print("== parent")
P, parent = login("parent@davdugda.in", "parent123", "parent")
check("parent has 2 children", len(parent.get("children", [])) == 2, parent.get("children"))
kids = parent["children"]
d = expect("parent dashboard", "GET", "dashboard", P); check("parent dashboard stats", len(d["stats"]) == 4, d)
expect("dashboard for 2nd child", "GET", "dashboard?student_id=%d" % kids[1]["id"], P)
att = expect("child attendance", "GET", "attendance/my?student_id=%d" % kids[0]["id"], P); check("attendance % present", att["percentage"] > 0, att)
rc = expect("child report card", "GET", "report-card/my?student_id=%d" % kids[0]["id"], P); check("parent report card", rc["subjects"], rc)
code, js = call("GET", "students/%d/report-card?exam_id=%d" % (kids[0]["id"], pt2["id"]), P)
check("unpublished exam hidden from parent (403)", code == 403, js)
invs = expect("child invoices", "GET", "invoices", P); check("only own children's invoices", all(i["student_id"] in [k["id"] for k in kids] for i in invs))
expect("child homework", "GET", "homework?student_id=%d" % kids[0]["id"], P)
expect("child timetable", "GET", "timetable?student_id=%d" % kids[0]["id"], P)
expect("notices", "GET", "notices", P)
expect("events", "GET", "events", P)
tr = expect("transport", "GET", "transport/routes", P); check("child's route flagged", any(r["is_mine"] for r in tr), tr)
expect("apply leave for child", "POST", "leaves", P, {"student_id": kids[0]["id"], "from_date": today, "to_date": today, "reason": "Family function"}, 201)
other = [s for s in studs if s["id"] not in [k["id"] for k in kids]][0]
code, js = call("GET", "students/%d" % other["id"], P)
check("parent cannot open other student (403)", code == 403, js)
contacts = expect("parent contacts", "GET", "messages/contacts", P); check("parent can message teachers", any(c["role"] == "teacher" for c in contacts), contacts)
tch = next(c for c in contacts if c["role"] == "teacher")
expect("parent messages teacher", "POST", "messages", P, {"receiver_id": tch["id"], "subject": "Hello", "body": "Namaste"}, 201)
code, js = call("POST", "attendance/students", P, {"section_id": 1, "date": today, "records": []})
check("parent cannot mark attendance (403)", code == 403, js)

print("== student")
S, student = login("student@davdugda.in", "student123", "student")
check("student profile has class", student["student"]["class_label"] == "Class 8 \u2013 A", student.get("student"))
expect("student dashboard", "GET", "dashboard", S)
expect("student attendance", "GET", "attendance/my", S)
expect("student report", "GET", "report-card/my", S)
hws = expect("student homework", "GET", "homework", S); check("student sees teacher's new homework", any(h["id"] == hw["id"] for h in hws))
expect("submit homework", "POST", "homework/%d/submit" % hw["id"], S, {"content": "Done, pages 4-5"})
subm = expect("teacher sees submission", "GET", "homework/%d/submissions" % hw["id"], T); check("submission visible to teacher", len(subm) == 1, subm)
expect("teacher reviews", "POST", "submissions/%d/review" % subm[0]["id"], T, {"status": "checked", "grade": "A", "feedback": "Good"})
expect("student books", "GET", "library/issues", S)
expect("student fees", "GET", "invoices", S)
expect("student timetable", "GET", "timetable", S)
code, js = call("GET", "users", S)
check("student cannot list users (403)", code == 403, js)
expect("change password", "POST", "auth/change-password", S, {"current_password": "student123", "new_password": "student456"})
expect("change password back", "POST", "auth/change-password", S, {"current_password": "student456", "new_password": "student123"})
expect("logout", "POST", "auth/logout", S)
code, js = call("GET", "auth/me", S)
check("token revoked after logout (401)", code == 401, js)

print(f"\n{passed} passed, {len(failed)} failed")
for f in failed: print("  -", f)
sys.exit(1 if failed else 0)
