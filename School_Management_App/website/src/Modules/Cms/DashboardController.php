<?php

namespace Website\Modules\Cms;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Website\Modules\Admissions\Enquiry;
use Website\Modules\Contact\ContactMessage;
use Website\Modules\Events\Event;
use Website\Modules\Gallery\Photo;
use Website\Modules\Notices\Notice;
use Website\Modules\Pages\Page;

class DashboardController extends Controller
{
    public function index()
    {
        return view('website::cms.dashboard', [
            'counts' => [
                'New enquiries' => [Enquiry::where('status', 'new')->count(), 'inbox', route('webadmin.inbox', 'enquiries')],
                'New messages' => [ContactMessage::where('status', 'new')->count(), 'mail', route('webadmin.inbox', 'messages')],
                'Notices' => [Notice::count(), 'megaphone', route('webadmin.r.index', 'notices')],
                'Upcoming events' => [Event::where('starts_at', '>=', now()->startOfDay())->count(), 'calendar-days', route('webadmin.r.index', 'events')],
                'Gallery photos' => [Photo::count(), 'image', route('webadmin.r.index', 'gallery')],
                'Pages' => [Page::count(), 'file-pen-line', route('webadmin.r.index', 'pages')],
            ],
            'enquiries' => Enquiry::latest()->limit(5)->get(),
            'checklist' => self::checklist(),
            'log' => DB::table('site_change_log')->orderByDesc('id')->limit(8)->get(),
        ]);
    }

    /** Sections that are still empty (hidden on the public site until the school fills them in). */
    public static function checklist(): array
    {
        $s = \Website\Support\Settings::all();
        $set = route('webadmin.settings');
        $r = fn ($k) => route('webadmin.r.index', $k);
        $empty = fn ($k) => trim((string) ($s[$k] ?? '')) === '';
        $pageEmpty = fn ($slug) => trim(strip_tags((string) DB::table('site_pages')->where('slug', $slug)->value('body'))) === '';
        $items = [
            ['Office phone number', $empty('phone'), $set],
            ['School email address', $empty('email'), $set],
            ['Office hours / school timings', $empty('office_hours'), $set],
            ['Google Maps location', $empty('map_embed_url'), $set],
            ["Principal's photo", $empty('principal_photo'), $set],
            ["Principal's welcome (home page)", $empty('principal_excerpt'), $set],
            ["Principal's message page", $pageEmpty('principal-message'), $r('pages')],
            ['Fee structure', ! DB::table('site_downloads')->where('category', 'fees')->exists(), $r('downloads')],
            ['Teaching & office staff list', ! DB::table('site_people')->whereIn('group', ['teaching', 'office'])->exists(), $r('people')],
            ['Notices', ! DB::table('site_notices')->exists(), $r('notices')],
            ['Events', ! DB::table('site_events')->exists(), $r('events')],
            ['Recent results (2023 onwards)', ! DB::table('site_achievements')->where('year', '>=', '2023')->exists(), $r('achievements')],
            ['Admissions banner (session & classes)', $empty('admission_banner'), $set],
            ['Social media links', $empty('facebook') && $empty('instagram') && $empty('youtube') && $empty('x'), $set],
            ['Testimonials', ! DB::table('site_testimonials')->exists(), $r('testimonials')],
            ['Career openings', ! DB::table('site_vacancies')->exists(), $r('vacancies')],
            ['Mandatory disclosure documents', DB::table('site_disclosures')->where('value', '')->orWhereNull('value')->exists(), $r('disclosure')],
        ];

        return array_map(fn ($i) => ['label' => $i[0], 'empty' => (bool) $i[1], 'url' => $i[2]], $items);
    }

    public function log()
    {
        return view('website::cms.log', ['log' => DB::table('site_change_log')->orderByDesc('id')->paginate(40)]);
    }
}
