# Downloads – DAV School Management System v2.0.0

The installers are too large to keep inside this git repository, so they are served from the free demo server
(and the public website's Downloads section). Check a download against its SHA-256 if you like
(`certutil -hashfile FILE SHA256` on Windows, `sha256sum FILE` on Linux/Mac).

| File | Size | Link | SHA-256 |
|---|---|---|---|
| `DAV_School_Admin_v2.0.0.apk` | 18.4 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_School_Admin_v2.0.0.apk) | `2aa5250b32bd88ab07418a799742b61274438ed403113e9fde4d36e47e14a151` |
| `DAV_Staff_v2.0.0.apk` | 18.4 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_Staff_v2.0.0.apk) | `016e25b46870b9d80e34b14bb4eefc0c4e4e5380db90efa5543a2c4b39691f1a` |
| `DAV_Parent_v2.0.0.apk` | 18.4 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_Parent_v2.0.0.apk) | `2212801d62a7bc672232f97bc58e05201f2edc3d5809d2d58dc9d349aa58e03a` |
| `DAV_Student_v2.0.0.apk` | 18.4 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_Student_v2.0.0.apk) | `b177e8779a8bb3a050a146fa9de74a9c6b3f8efab3398080d64ceda0795b617c` |
| `DAV_School_Management_Setup_2.0.0.exe` | 72.3 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_School_Management_Setup_2.0.0.exe) | `fe1bb94845cc8eb39d6eac1cbba83d1a218329e26a478f161516905d2a8f109e` |
| `DAV_School_Management_Portable_2.0.0.zip` | 97.7 MB | [Download](https://dav-dugda-sms.sites.tab.bot/downloads/DAV_School_Management_Portable_2.0.0.zip) | `c4007b8fcbe69e0412e98f3faf233d361bbd85ad46cef92959d9bb2997686ad4` |

## Which file do I need?

- **Android phones** – install the APK for the person's role:
  School Admin (principal / office), Staff (teachers, accountant, librarian), Parent, Student.
  On the phone allow *Install unknown apps* for WhatsApp / Files / Chrome when asked.
- **Windows PC** – `DAV_School_Management_Setup_2.0.0.exe` installs the PC software (Start-menu + desktop shortcut).
  `..._Portable_2.0.0.zip` needs no install: unzip and run `DAV School Management.exe`.
  The app is not code-signed (signing costs money), so Windows SmartScreen may say *Windows protected your PC* →
  click **More info → Run anyway**.
- **iPhone** – source code is in `ios/`; it needs a Mac with Xcode to build (see `ios/README.md`).

All apps start with the demo server. Point them at your own server from the login screen → **Server settings**
(Windows: first start, or File → Server settings). See `docs/GO_LIVE_GUIDE.md`.

## Optional: put the files on a GitHub Release page

1. On a PC browser open https://github.com/RIP09/dav_dugda_bokaro_jharkhand_828404/releases/new
2. Tag: `v2.0.0`, title: `DAV School Management 2.0.0`.
3. Drag the six files from the table above into the *Attach binaries* box, wait for the uploads, click **Publish release**.

Excel import templates: `docs/import_templates/` (29 ready sheets, each with a *How to fill* tab).
