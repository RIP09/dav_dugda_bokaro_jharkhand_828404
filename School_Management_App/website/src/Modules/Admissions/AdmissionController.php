<?php

namespace Website\Modules\Admissions;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Website\Modules\Downloads\Download;
use Website\Modules\Pages\Page;

class AdmissionController extends Controller
{
    public const CLASSES = ['Nursery', 'LKG', 'UKG', 'Class I', 'Class II', 'Class III', 'Class IV', 'Class V', 'Class VI', 'Class VII', 'Class VIII', 'Class IX', 'Class X', 'Class XI (Science)', 'Class XI (Commerce)', 'Class XII'];

    public function show()
    {
        return view('website::site.admissions.show', [
            'page' => Page::where('slug', 'admissions')->first(),
            'classes' => self::CLASSES,
            'forms' => Download::published()->whereIn('category', ['prospectus', 'forms', 'fees'])->orderBy('sort')->get(),
        ]);
    }

    public function submit(Request $request)
    {
        if ($request->filled('website')) {   // honeypot: bots fill hidden fields
            return redirect()->route('website.admissions')->with('enquiry_ok', true);
        }
        $key = 'site-enquiry:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput()->withErrors(['phone' => 'Too many enquiries from this connection. Please try again in a few minutes or call the school office.']);
        }
        RateLimiter::hit($key, 600);

        $data = $request->validate([
            'student_name' => 'required|string|max:120',
            'parent_name' => 'required|string|max:120',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{10,16}$/'],
            'email' => 'nullable|email|max:150',
            'class_sought' => 'required|string|max:40',
            'dob' => 'nullable|date|before:today',
            'previous_school' => 'nullable|string|max:150',
            'message' => 'nullable|string|max:2000',
        ], [
            'phone.regex' => 'Please enter a valid 10-digit mobile number.',
        ]);
        $data['ip'] = $request->ip();
        $enquiry = Enquiry::create($data);

        // Hand the enquiry to the school-management system too, so the office sees it in the apps.
        try {
            if (Schema::hasTable('enquiries')) {
                $row = [
                    'student_name' => $data['student_name'],
                    'parent_name' => $data['parent_name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'class_sought' => $data['class_sought'],
                    'dob' => $data['dob'] ?? null,
                    'previous_school' => $data['previous_school'] ?? null,
                    'message' => $data['message'] ?? null,
                    'source' => 'website',
                    'status' => 'new',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $cols = Schema::getColumnListing('enquiries');
                $id = DB::table('enquiries')->insertGetId(array_intersect_key($row, array_flip($cols)));
                $enquiry->forceFill(['school_enquiry_id' => $id])->saveQuietly();
            }
        } catch (\Throwable $e) {
            Log::warning('Website enquiry could not be copied to the management system: '.$e->getMessage());
        }

        return redirect()->to(route('website.admissions').'#enquiry')->with('enquiry_ok', $enquiry->student_name);
    }
}
