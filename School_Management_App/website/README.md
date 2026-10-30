# DAV Public School, Dugda — public website + Website CMS

A Laravel package loaded by `backend/` (see `backend/bootstrap/providers.php`; autoload `Website\\` → `../website/src/`).

- Public site at `/` — home (hero slider, real counters, highlights, facilities, results, gallery), About, Our Principal,
  Management, Academics, Facilities, Admissions (online enquiry), Notices, Events, Gallery, Results, Downloads, Contact,
  Mandatory Disclosure. Scroll-reveal and counter animations respect `prefers-reduced-motion`.
- Website CMS at `/website-admin` — its own login (table `site_admins`, separate from the school-management users).
  Default: `webadmin@davdugda.in` / `webadmin123` (owner) — change it after first sign-in.
  Every page text, setting, slide, counter, highlight, notice, event, album/photo, staff member, result, facility,
  download, vacancy, disclosure row and menu item is editable; admission enquiries and contact messages are in the inbox
  (CSV export); the dashboard lists every section that is still empty (**Still to fill in**) — empty sections are hidden on the site.

## Content rule
Only facts verified in `docs/school_facts.md` are seeded (`src/Database/WebsiteSeeder.php`). Phone, email, office hours,
fees, staff list, principal's message/photo and map are intentionally empty until the school confirms them.
Photos in `public-assets/img/photos/` are the school's own.

## Commands
- `php artisan migrate --seed` — creates the website tables and starting content (skipped if content already exists).
- `php artisan website:install --fresh` — wipe website content and load the starting content again.

## Hosting note
With `APP_ENV=production` and an `https://` `APP_URL`, all generated links use `APP_URL` (needed behind a proxy).
