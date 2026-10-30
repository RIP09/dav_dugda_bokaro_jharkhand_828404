<?php

namespace Website\Database;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Starting content for the DAV Public School, Dugda website. Everything here is editable in the
 * Website CMS (/website-admin). Only facts verified in docs/school_facts.md are seeded;
 * anything unconfirmed is left empty (hidden on the site, listed on the CMS checklist).
 */
class WebsiteSeeder extends Seeder
{
    /** Set to true (website:install --fresh) to wipe website content and load the starting content again. */
    public static bool $force = false;

    public function run(): void
    {
        // Idempotent: never overwrite a website that already has content (the school's own edits).
        if (! self::$force && DB::table('site_admins')->exists()) {
            $this->command?->info('Website content already present — skipped (use php artisan website:install --fresh to reload the demo content).');

            return;
        }
        $now = now();
        foreach (['site_change_log', 'site_messages', 'site_enquiries', 'site_vacancies', 'site_disclosures', 'site_facilities', 'site_testimonials',
            'site_downloads', 'site_achievements', 'site_people', 'site_photos', 'site_albums', 'site_events', 'site_notices', 'site_highlights',
            'site_stats', 'site_banners', 'site_menu_items', 'site_pages', 'site_settings', 'site_admins'] as $t) {
            DB::table($t)->delete();
        }

        // ---------- Website admin accounts (separate from the school-management login) ----------
        DB::table('site_admins')->insert([
            ['name' => 'Website Admin', 'email' => 'webadmin@davdugda.in', 'password' => Hash::make('webadmin123'), 'role' => 'owner', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Office Editor', 'email' => 'editor@davdugda.in', 'password' => Hash::make('editor123'), 'role' => 'editor', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---------- Settings ----------
        // HARD RULE: only facts verified in docs/school_facts.md. Anything not confirmed by the school
        // (phone, email, timings, fees, staff, principal's message, map pin) is left EMPTY: the site hides it and
        // the CMS dashboard lists it under "Still to fill in".
        $settings = [
            'site_name' => 'DAV Public School, Dugda',
            'site_short_name' => 'DAV Dugda',
            'tagline' => 'A DAV institution sponsored by BCCL',
            'affiliation_text' => 'Affiliated to CBSE, New Delhi (No. 3430146) · DAV CMC & DAV CAE',
            'affiliation_no' => '3430146',
            'school_code' => '66268',
            'established_year' => '2000',
            'principal_name' => 'Prasanjeet Kumar Paul',
            'principal_title' => 'Principal',
            'principal_qualifications' => 'M.Sc. (Zoology), B.Ed.',
            'principal_experience' => '31 years of teaching and 22 years of administrative experience',
            'principal_excerpt' => '',
            'address' => "D.A.V. Public School, Dugda\nDugda Coal Washery Township (BCCL), P.O. Dugda\nDistt. Bokaro, Jharkhand – 828404",
            'phone' => '', 'phone_alt' => '', 'email' => '', 'office_hours' => '', 'whatsapp' => '', 'map_embed_url' => '',
            'facebook' => '', 'instagram' => '', 'youtube' => '', 'x' => '',
            'admission_banner' => '',
            'admissions_open' => '0',
            'home_intro_title' => 'A CBSE school in the BCCL township at Dugda',
            'home_intro_text' => 'DAV Public School, Dugda is a CBSE-affiliated senior secondary school, from Nursery to Class XII, in the Dugda Coal Washery Township, Bokaro. It is a project school sponsored by Bharat Coking Coal Ltd. (BCCL), a subsidiary of Coal India Ltd, and is run by the DAV College Managing Committee, New Delhi.',
            'color_primary' => '#1B2A7A',
            'color_accent' => '#F5A300',
            'footer_text' => 'A project school sponsored by BCCL (Coal India Ltd) and run by the DAV College Managing Committee, New Delhi. Affiliated to CBSE, New Delhi — Affiliation No. 3430146, School Code 66268.',
            'copyright' => '© '.date('Y').' DAV Public School, Dugda. All rights reserved.',
            'meta_description' => 'DAV Public School, Dugda (Bokaro, Jharkhand) — CBSE school from Nursery to Class XII, Science and Commerce. Affiliation No. 3430146, School Code 66268. Sponsored by BCCL, run by DAV CMC New Delhi.',
            'meta_keywords' => 'DAV Dugda, DAV Public School Dugda, CBSE school Bokaro, Dugda Coal Washery, BCCL school, 3430146',
            'logo' => 'asset:img/logo.webp', 'favicon' => 'asset:img/favicon.png', 'og_image' => 'asset:img/photos/front.jpg', 'principal_photo' => '',
        ];
        foreach ($settings as $k => $v) {
            DB::table('site_settings')->insert(['key' => $k, 'value' => $v, 'created_at' => $now, 'updated_at' => $now]);
        }

        // ---------- Pages ----------
        $pages = [
            ['about', 'About Our School', 'A DAV institution in the BCCL township at Dugda', 'front', <<<'HTML'
<h2>Our story</h2>
<p>D.A.V. Public School, Dugda came into existence when the school run by the Kendriya Vidyalaya Sangathan was handed over to the D.A.V. organisation. It is a project school sponsored by Bharat Coking Coal Ltd. (BCCL), a subsidiary of Coal India Ltd, and the entire infrastructure has been provided by BCCL.</p>
<p>The school stands in the BCCL township at Dugda Coal Washery, Bokaro. CBSE records the school's year of foundation as 2000 and its first opening as 1 July 2005.</p>
<h2>The school today</h2>
<p>The school is affiliated to the Central Board of Secondary Education up to the Senior Secondary level (10+2), with classes from Nursery to Class XII in three wings — the Kids wing, the Junior wing and the Senior wing. In Classes XI and XII the school offers the Science and Commerce streams.</p>
<h2>Our aims</h2>
<ul>
<li>To provide sound fundamental knowledge and education to every child.</li>
<li>To light the lamp of knowledge in the heart of every student.</li>
<li>To teach with new and innovative methods in addition to the basic subjects.</li>
<li>To develop each child's personality in a balanced way — strong character, a cohesive personality and a healthy mental attitude.</li>
</ul>
<h2>Value-based education</h2>
<p>Through its value-based educational programmes, the school gives students an insight into the spiritual and moral values reflected in our rich cultural heritage. Positive habits — personal hygiene, a neat uniform, clean surroundings, graceful manners, obedience and discipline — are part of everyday school life.</p>
<h2>Objectives</h2>
<ul>
<li>To help children in their social development.</li>
<li>To encourage children to develop their own creative ability and to express it.</li>
<li>To give children a wealth of experience to understand the world around them.</li>
<li>To initiate the child towards aesthetic appreciation.</li>
<li>To inculcate positive values through prayers, poems and stories.</li>
</ul>
<h2>Affiliation</h2>
<p>Central Board of Secondary Education, New Delhi — Affiliation No. <strong>3430146</strong>, School Code <strong>66268</strong>. Affiliation period: 1 April 2025 to 31 March 2031 (as listed by CBSE).</p>
HTML],
            ['principal-message', 'Our Principal', 'Prasanjeet Kumar Paul', null, ''],
            ['management', 'School Management', 'DAV College Managing Committee, New Delhi', null, <<<'HTML'
<p>DAV Public School, Dugda is run by the DAV College Managing Committee (DAV CMC), New Delhi. It is a project school sponsored by Bharat Coking Coal Ltd. (BCCL), a subsidiary of Coal India Ltd, which provided the school's entire infrastructure.</p>
<h2>Internal Quality Assurance Cell (IQAC)</h2>
<p>The Internal Quality Assurance Cell is constituted in the school under the Principal, with heads of important academic and administrative units, a few teachers, a few distinguished educationists and representatives of the local management.</p>
HTML],
            ['academics', 'Academics', 'CBSE curriculum from Nursery to Class XII', 'pathway', <<<'HTML'
<h2>Curriculum</h2>
<p>The school follows the curriculum of the Central Board of Secondary Education (CBSE), New Delhi, from Nursery to Class XII. Classes XI and XII offer two streams: <strong>Science</strong> and <strong>Commerce</strong>.</p>
<h2>Three wings</h2>
<ul><li><strong>Kids wing</strong> — the youngest learners</li><li><strong>Junior wing</strong></li><li><strong>Senior wing</strong> — up to Class XII</li></ul>
<h2>Laboratories</h2>
<p>Six well-equipped laboratories, including Physics, Chemistry, Biology and Computer labs and an Art lab that accommodates forty students at a time.</p>
<h2>Sports</h2>
<p>Training is given in cricket, basketball, table tennis, kho-kho and hockey.</p>
<h2>Co-curricular activities</h2>
<p>Dramatization, declamation, debate, song, music, dance, quiz and extempore. Morning assembly and Hawan are part of school life.</p>
HTML],
            ['admissions', 'Admissions', 'Join DAV Public School, Dugda', 'gate', <<<'HTML'
<p>The school has classes from Nursery to Class XII, with Science and Commerce in Classes XI and XII.</p>
<p>Admission dates, forms and fee details for each session are published by the school office in the Notices section. You can send an admission enquiry using the form on this page — it goes straight to the school office.</p>
HTML],
            ['facilities', 'Facilities', 'Our campus in the BCCL township', 'swings', '<p>The school\'s entire infrastructure has been provided by Bharat Coking Coal Ltd. (BCCL).</p>'],
            ['faculty', 'Our Faculty', null, null, ''],
            ['mandatory-disclosure', 'Mandatory Public Disclosure', 'As required by the Central Board of Secondary Education', null, '<p>Information published as per Appendix-IX of the CBSE Affiliation Bye-Laws. Items shown as “—” will be added by the school office.</p>'],
            ['careers', 'Careers', null, null, '<p>Vacancies, when open, are announced here and in Notices.</p>'],
            ['contact', 'Contact Us', 'Visit the school or send us a message', null, '<p>Send a message with the form below — it goes straight to the school office.</p>'],
            ['privacy-policy', 'Privacy Policy', null, null, '<p>Information you share through this website (enquiry and contact forms) is used only by the school office to reply to you. It is not sold or shared with anyone.</p>'],
        ];
        foreach ($pages as [$slug, $title, $sub, $img, $body]) {
            DB::table('site_pages')->insert([
                'slug' => $slug, 'title' => $title, 'subtitle' => $sub, 'body' => $body,
                'image' => $img ? "asset:img/photos/{$img}.jpg" : null,
                'is_published' => true, 'is_system' => $slug !== 'privacy-policy', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ---------- Menus (sections that are still empty — faculty, notices, events, careers — are left out until filled) ----------
        $menu = [
            ['Home', '/', []],
            ['About', '/about', [['About the School', '/about'], ['Our Principal', '/principal-message'], ['Management', '/management'], ['Mandatory Disclosure', '/mandatory-disclosure']]],
            ['Academics', '/academics', [['Curriculum', '/academics'], ['Facilities', '/facilities'], ['Results', '/achievements']]],
            ['Admissions', '/admissions', []],
            ['Gallery', '/gallery', []],
            ['Downloads', '/downloads', []],
            ['Contact', '/contact', []],
        ];
        $i = 0;
        foreach ($menu as [$label, $url, $kids]) {
            $id = DB::table('site_menu_items')->insertGetId(['location' => 'header', 'label' => $label, 'url' => $url, 'sort' => ++$i * 10, 'is_visible' => true, 'created_at' => $now, 'updated_at' => $now]);
            $j = 0;
            foreach ($kids as [$kl, $ku]) {
                DB::table('site_menu_items')->insert(['location' => 'header', 'label' => $kl, 'url' => $ku, 'parent_id' => $id, 'sort' => ++$j, 'is_visible' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        foreach ([['About', '/about'], ['Admissions', '/admissions'], ['Downloads', '/downloads'], ['Results', '/achievements'], ['Mandatory Disclosure', '/mandatory-disclosure'], ['Privacy Policy', '/page/privacy-policy']] as $k => [$l, $u]) {
            DB::table('site_menu_items')->insert(['location' => 'footer', 'label' => $l, 'url' => $u, 'sort' => $k, 'is_visible' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // ---------- Home slider (real school photos) ----------
        $slides = [
            ['CBSE Affiliation No. 3430146', 'DAV Public School, Dugda', 'Nursery to Class XII · Science & Commerce · Dugda Coal Washery Township (BCCL), Bokaro', 'front', 'About the school', '/about'],
            ['Sponsored by BCCL', 'A green campus in the BCCL township', 'Kids, Junior and Senior wings — the entire infrastructure provided by Bharat Coking Coal Ltd.', 'pathway', 'Our facilities', '/facilities'],
            ['DAV values', 'Hawan and morning assembly', 'Part of everyday school life, alongside sports, music and co-curricular activities.', 'havan', 'Academics', '/academics'],
            ['Admissions', 'Send an admission enquiry', 'Your enquiry goes straight to the school office.', 'gate', 'Enquire now', '/admissions#enquiry'],
        ];
        foreach ($slides as $k => [$eb, $t, $st, $img, $bt, $bu]) {
            DB::table('site_banners')->insert(['eyebrow' => $eb, 'title' => $t, 'subtitle' => $st, 'image' => "asset:img/photos/{$img}.jpg", 'button_text' => $bt, 'button_url' => $bu, 'sort' => $k, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // ---------- Counters (real numbers only) ----------
        foreach ([['Well-equipped laboratories', 6, '', 'flask-conical'], ['Wings: Kids, Junior & Senior', 3, '', 'school'], ['Streams in Classes XI–XII', 2, '', 'graduation-cap'], ["Years of teaching — our Principal", 31, '', 'award']] as $k => [$l, $v, $sf, $ic]) {
            DB::table('site_stats')->insert(['label' => $l, 'value' => $v, 'suffix' => $sf, 'icon' => $ic, 'sort' => $k, 'created_at' => $now, 'updated_at' => $now]);
        }

        // ---------- Highlights ----------
        $hl = [
            ['CBSE, Nursery to XII', 'Affiliated to CBSE up to Senior Secondary — Affiliation No. 3430146, School Code 66268 — with Science and Commerce in Classes XI–XII.', 'book-open'],
            ['A DAV institution', 'Run by the DAV College Managing Committee, New Delhi, with morning assembly and Hawan as part of school life.', 'hand-heart'],
            ['Sponsored by BCCL', 'A project school of Bharat Coking Coal Ltd., a subsidiary of Coal India Ltd, which provided the entire infrastructure.', 'shield-check'],
            ['Six laboratories', 'Physics, Chemistry, Biology, Computer and Art labs — the Art lab seats forty students at a time.', 'flask-conical'],
            ['Sports', 'Training in cricket, basketball, table tennis, kho-kho and hockey.', 'trophy'],
            ['Beyond the classroom', 'Dramatization, declamation, debate, song, music, dance, quiz and extempore.', 'palette'],
        ];
        foreach ($hl as $k => [$t, $s, $ic]) {
            DB::table('site_highlights')->insert(['title' => $t, 'summary' => $s, 'icon' => $ic, 'sort' => $k, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // Notices, events, testimonials, vacancies: none seeded — the school posts real ones in the CMS.

        // ---------- Gallery (the school's own photos) ----------
        $albums = [
            ['Our Campus', ['front' => 'The main school building', 'pathway' => 'Bougainvillea-lined pathway', 'arch' => 'The flower arch walkway', 'lotus' => 'Lotus fountain garden', 'swings' => 'Play area']],
            ['School Life', ['gate' => 'The main gate on a celebration day', 'havan' => 'Hawan with students and staff']],
        ];
        foreach ($albums as $k => [$title, $photos]) {
            $aid = DB::table('site_albums')->insertGetId(['title' => $title, 'slug' => Str::slug($title), 'description' => null, 'cover' => 'asset:img/photos/'.array_key_first($photos).'.jpg', 'event_date' => null, 'sort' => $k, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
            $s = 0;
            foreach ($photos as $img => $cap) {
                DB::table('site_photos')->insert(['album_id' => $aid, 'image' => "asset:img/photos/{$img}.jpg", 'caption' => $cap, 'sort' => ++$s, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // ---------- People: only the Principal (verified). Teaching/office staff are added by the school. ----------
        DB::table('site_people')->insert(['group' => 'management', 'name' => 'Prasanjeet Kumar Paul', 'designation' => 'Principal', 'department' => null, 'qualification' => 'M.Sc. (Zoology), B.Ed.', 'experience' => '31 years teaching', 'sort' => 0, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);

        // ---------- Results: CBSE 2022 school toppers (Hindustan, 23 Jul 2022). Newer results are added by the school. ----------
        $ach = [
            ['board', 'Class XII Science — School Topper (CBSE)', 'Mridul Kumar', 'XII', '96.6%'],
            ['board', 'Class X — School Topper (CBSE)', 'Ayush Patel', 'X', '97.4%'],
            ['board', 'Class XII Commerce — School Topper (CBSE)', 'Abhinav Sharma', 'XII', '92.6%'],
            ['board', 'Class X — 2nd (CBSE)', 'Pranjal Kumar', 'X', '96.8%'],
            ['board', 'Class X — 3rd (CBSE)', 'Amit Kumar', 'X', '95.2%'],
            ['board', 'Class XII Science — 2nd (CBSE)', 'Mrinal Kumar', 'XII', '92.4%'],
            ['board', 'Class XII Science — 3rd (CBSE)', 'Neha Kumari', 'XII', '92%'],
            ['board', 'Class XII Commerce — 2nd (joint) (CBSE)', 'Megha Kumari', 'XII', '91%'],
            ['board', 'Class XII Commerce — 2nd (joint) (CBSE)', 'Sankalp Arya', 'XII', '91%'],
            ['board', 'Class XII Commerce — 3rd (CBSE)', 'Komal Kumari', 'XII', '83.4%'],
        ];
        foreach ($ach as $k => [$c, $t, $s, $cl, $sc]) {
            DB::table('site_achievements')->insert(['category' => $c, 'title' => $t, 'student_name' => $s, 'class_name' => $cl, 'score' => $sc, 'year' => '2022', 'sort' => $k, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // ---------- Downloads: the school management apps v2.0.1 + go-live guide (served from /downloads/) ----------
        $dl = [
            ['School Admin app (Android)', 'apps', '/downloads/DAV_School_Admin_v2.0.1.apk', 'For the principal and school office · version 2.0.1'],
            ['Staff app (Android)', 'apps', '/downloads/DAV_Staff_v2.0.1.apk', 'For teachers and staff · version 2.0.1'],
            ['Parent app (Android)', 'apps', '/downloads/DAV_Parent_v2.0.1.apk', 'For parents · version 2.0.1'],
            ['Student app (Android)', 'apps', '/downloads/DAV_Student_v2.0.1.apk', 'For students · version 2.0.1'],
            ['School Management for Windows — installer', 'apps', '/downloads/DAV_School_Management_Setup_2.0.1.exe', 'Windows 10/11 setup · version 2.0.1'],
            ['School Management for Windows — portable', 'apps', '/downloads/DAV_School_Management_Portable_2.0.1.zip', 'No install needed: unzip and run · version 2.0.1'],
            ['Go-Live Guide (PDF)', 'guides', '/downloads/DAV_Go_Live_Guide.pdf', 'How to put the school management system live'],
        ];
        foreach ($dl as $k => [$t, $c, $f, $d]) {
            DB::table('site_downloads')->insert(['title' => $t, 'category' => $c, 'file' => $f, 'description' => $d, 'sort' => $k, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // ---------- Facilities (named by the school; descriptions only where verified) ----------
        $fac = [
            ['School Building', 'school', 'front', 'Provided by BCCL in the Dugda Coal Washery Township.'],
            ['Physics Lab', 'flask-conical', null, 'One of the school\'s six laboratories.'],
            ['Chemistry Lab', 'flask-conical', null, 'One of the school\'s six laboratories.'],
            ['Biology Lab', 'microscope', null, 'One of the school\'s six laboratories.'],
            ['Computer Lab', 'monitor', null, 'One of the school\'s six laboratories.'],
            ['Art Lab', 'palette', null, 'Accommodates forty students at a time.'],
            ['Library', 'library', null, 'The school library.'],
            ['Playground', 'trophy', 'swings', 'Cricket, basketball, table tennis, kho-kho and hockey.'],
            ['Gardens', 'leaf', 'lotus', 'Flower-lined walkways and the lotus fountain garden.'],
        ];
        foreach ($fac as $k => [$t, $ic, $img, $s]) {
            DB::table('site_facilities')->insert(['title' => $t, 'slug' => Str::slug($t), 'icon' => $ic, 'summary' => $s, 'body' => "<p>{$s}</p>", 'image' => $img ? "asset:img/photos/{$img}.jpg" : null, 'sort' => $k, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // ---------- Mandatory Public Disclosure (CBSE Appendix-IX). Verified values filled; the rest empty ("—"). ----------
        $disc = [
            ['General Information', 'Name of the school', 'DAV Public School, Dugda'],
            ['General Information', 'Affiliation No.', '3430146'],
            ['General Information', 'School code', '66268'],
            ['General Information', 'Complete address with pin code', 'Dugda Coal Washery Township (BCCL), P.O. Dugda, Distt. Bokaro, Jharkhand – 828404'],
            ['General Information', 'Principal name', 'Prasanjeet Kumar Paul'],
            ['General Information', 'Principal qualification', 'M.Sc. (Zoology), B.Ed.'],
            ['General Information', 'School email ID', ''],
            ['General Information', 'Contact details (landline / mobile)', ''],
            ['General Information', 'Status of affiliation', 'Senior Secondary'],
            ['General Information', 'Affiliation period', '01/04/2025 to 31/03/2031'],
            ['General Information', 'Name of trust / society', 'DAV College Managing Committee, New Delhi'],
            ['Documents and Information', 'Copies of affiliation / upgradation letter and recent extension of affiliation', ''],
            ['Documents and Information', 'Copies of societies / trust / company registration / renewal certificate', ''],
            ['Documents and Information', 'Copy of No Objection Certificate (NOC) issued by the State Govt./UT', ''],
            ['Documents and Information', 'Copies of recognition certificate under RTE Act, 2009', ''],
            ['Documents and Information', 'Copy of valid building safety certificate', ''],
            ['Documents and Information', 'Copy of valid fire safety certificate', ''],
            ['Documents and Information', 'Copy of the DEO certificate submitted by the school for affiliation / upgradation / extension', ''],
            ['Documents and Information', 'Copies of valid water, health and sanitation certificates', ''],
            ['Result and Academics', 'Fee structure of the school', ''],
            ['Result and Academics', 'Annual academic calendar', ''],
            ['Result and Academics', 'List of School Management Committee (SMC)', ''],
            ['Result and Academics', 'List of Parents Teachers Association (PTA) members', ''],
            ['Result and Academics', 'Last three-year result of the board examination', ''],
            ['Staff (Teaching)', 'Principal', 'Prasanjeet Kumar Paul'],
            ['Staff (Teaching)', 'Total no. of teachers (PGT / TGT / PRT)', ''],
            ['Staff (Teaching)', 'Teacher–section ratio', ''],
            ['Staff (Teaching)', 'Details of special educator', ''],
            ['Staff (Teaching)', 'Details of counsellor and wellness teacher', ''],
            ['School Infrastructure', 'Total campus area of the school (in sq. mtr)', ''],
            ['School Infrastructure', 'No. and size of the classrooms (in sq. mtr)', ''],
            ['School Infrastructure', 'No. and size of laboratories including computer labs (in sq. mtr)', ''],
            ['School Infrastructure', 'Internet facility (Y/N)', ''],
            ['School Infrastructure', 'No. of girls toilets', ''],
            ['School Infrastructure', 'No. of boys toilets', ''],
            ['School Infrastructure', 'Link of YouTube video of the inspection of school covering infrastructure', ''],
        ];
        foreach ($disc as $k => [$sec, $l, $v]) {
            DB::table('site_disclosures')->insert(['section' => $sec, 'label' => $l, 'value' => $v, 'sort' => $k, 'created_at' => $now, 'updated_at' => $now]);
        }
    }
}
