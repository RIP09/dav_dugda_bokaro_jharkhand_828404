# Downloads – DAV School Management System v2.0.1 (latest)

The installers are too large to keep inside this git repository, so they are served from the free demo server
(and the public website's Downloads page: https://dav-dugda-sms.sites.tab.bot/downloads). Check a download against its SHA-256 if you like
(`certutil -hashfile FILE SHA256` on Windows, `sha256sum FILE` on Linux/Mac).

| File | Size | Link | SHA-256 |
|---|---|---|---|
| `DAV_School_Admin_v2.0.1.apk` | 11.9 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_School_Admin_v2.0.1.apk) | `5aab342cb876c76abc69eb1a7b8d547ee146546ee8acf8beb376417ad70c59ee` |
| `DAV_Staff_v2.0.1.apk` | 11.9 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_Staff_v2.0.1.apk) | `3da64a2f38b33ffdc27637c12f1ffee276429a60ce9098717132aaa056b0110e` |
| `DAV_Parent_v2.0.1.apk` | 11.9 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_Parent_v2.0.1.apk) | `992112f6db309da7c9ce715daea1062ed3784b2936486d6d945486d390154908` |
| `DAV_Student_v2.0.1.apk` | 11.9 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_Student_v2.0.1.apk) | `a2a0e0a01d313874230db19d7ce60be53bfe0e71abaf6362b098b296ab001667` |
| `DAV_School_Management_Setup_2.0.1.exe` | 72.3 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_School_Management_Setup_2.0.1.exe) | `7a19a1e4de9998507782e604c2057a097103b01e63922379716b63dabf025d30` |
| `DAV_School_Management_Portable_2.0.1.zip` | 97.7 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_School_Management_Portable_2.0.1.zip) | `d91428521ed6ca391c564bc3844772de61cd8021a40145233b2ebd866c87fb62` |
| `DAV_Go_Live_Guide.pdf` | 1.2 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_Go_Live_Guide.pdf) | `a716688702aba734302db67ddcb8b4abb76eddc4d0c5a8e726b8671cec98c9aa` |

**What's new in 2.0.1:** every section now says its class ("Class 6 – A"), you pick the class first and only that class's
sections are offered, the server refuses a section from another class, and Class XI–XII sections can carry a stream
(Science / Commerce only). Details: `docs/RELEASE_NOTES_2.0.1.md`. The 2.0.1 Android apps install over 2.0.0 (same signing key).
Version 2.0.0 is no longer offered for download; 2.0.1 replaces it.

## Which file do I need?

- **Android phones** – install the APK for the person's role:
  School Admin (principal / office), Staff (teachers, accountant, librarian), Parent, Student.
  On the phone allow *Install unknown apps* for WhatsApp / Files / Chrome when asked.
- **Windows PC** – `DAV_School_Management_Setup_2.0.1.exe` installs the PC software (Start-menu + desktop shortcut).
  `..._Portable_2.0.1.zip` needs no install: unzip and run `DAV School Management.exe`.
  The app is not code-signed (signing costs money), so Windows SmartScreen may say *Windows protected your PC* →
  click **More info → Run anyway**.
- **iPhone** – source code is in `ios/`; it needs a Mac with Xcode to build (see `ios/README.md`).

All apps start with the demo server. Point them at your own server from the login screen → **Server settings**
(Windows: first start, or File → Server settings). See `docs/GO_LIVE_GUIDE.md`.

## Optional: put the files on a GitHub Release page

1. On a PC browser open https://github.com/RIP09/dav_dugda_bokaro_jharkhand_828404/releases/new
2. Tag: `v2.0.1`, title: `DAV School Management 2.0.1`.
3. Drag the six files from the table above into the *Attach binaries* box, wait for the uploads, click **Publish release**.

Excel import templates: `docs/import_templates/` (29 ready sheets, each with a *How to fill* tab).

## Public school website

Live demo: https://dav-dugda-sms.sites.tab.bot/ · Website CMS: https://dav-dugda-sms.sites.tab.bot/website-admin (login `webadmin@davdugda.in` / `webadmin123` — change it after first sign-in).
Source: `website/` (loaded by the backend; see `website/README.md`). Content facts and sources: `docs/school_facts.md`.
