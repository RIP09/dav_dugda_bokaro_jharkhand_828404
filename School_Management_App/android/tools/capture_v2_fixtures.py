#!/usr/bin/env python3
"""Captures real API v2 responses (every role) into core/src/test/resources/fixtures_v2/ for ApiV2ContractTest.
Usage: python3 tools/capture_v2_fixtures.py http://127.0.0.1:8090/api/v1/
"""
import json, os, sys, urllib.request, urllib.error, re

API = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8090/api/v1/").rstrip("/") + "/"
OUT = os.path.join(os.path.dirname(__file__), "..", "core", "src", "test", "resources", "fixtures_v2")
LOGINS = {
    "admin": ("principal@davdugda.in", "principal123", "school"),
    "teacher": ("teacher@davdugda.in", "teacher123", "staff"),
    "accountant": ("accountant@davdugda.in", "accountant123", "staff"),
    "librarian": ("librarian@davdugda.in", "librarian123", "staff"),
    "parent": ("parent@davdugda.in", "parent123", "parent"),
    "student": ("student@davdugda.in", "student123", "student"),
}

def call(path, token=None, body=None):
    req = urllib.request.Request(API + path, data=json.dumps(body).encode() if body is not None else None, method="POST" if body is not None else "GET")
    req.add_header("Accept", "application/json")
    if body is not None: req.add_header("Content-Type", "application/json")
    if token: req.add_header("Authorization", "Bearer " + token)
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return r.status, r.read().decode()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()

def save(role, name, text):
    fn = role + "__" + re.sub(r"[^a-z0-9]+", "_", name.lower()).strip("_") + ".json"
    with open(os.path.join(OUT, fn), "w") as f: f.write(text)

os.makedirs(OUT, exist_ok=True)
for f in os.listdir(OUT): os.remove(os.path.join(OUT, f))
summary = {}
for role, (login, pw, app) in LOGINS.items():
    st, txt = call("auth/login", body={"login": login, "password": pw, "app": app})
    if st != 200: print(role, "login failed", st, txt[:200]); continue
    tok = json.loads(txt)["data"]["token"]
    got = []
    for ep in ["school", "auth/me", "dashboard", "meta/modules", "meta/menu", "dashboard/charts", "notifications", "search?q=kumar", "notices"]:
        st, t = call(ep, tok)
        if st == 200: save(role, ep.replace("/", "_") if ep != "search?q=kumar" else "search", t); got.append(ep)
    st, t = call("meta/modules", tok)
    mods = json.loads(t)["data"] if st == 200 else []
    for m in mods:
        k = m["key"]
        st, t = call(f"records/{k}?per_page=5", tok)
        if st != 200: print(role, k, "list", st); continue
        save(role, f"records_{k}", t); got.append(k)
        rows = json.loads(t).get("data", [])
        if rows:
            rid = rows[0]["id"]
            st, t = call(f"records/{k}/{rid}", tok)
            if st == 200: save(role, f"record_{k}_one", t)
            if m.get("permissions", {}).get("history"):
                st, t = call(f"records/{k}/{rid}/history", tok)
                if st == 200: save(role, f"history_{k}", t)
    if role == "admin":
        st, t = call("audit?per_page=10", tok)
        if st == 200: save(role, "audit", t)
    summary[role] = len(got)
print(json.dumps(summary))
