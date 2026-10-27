<?php

use App\Modules\Academics\Models\AcademicYear;
use App\Modules\Exams\Models\Grade;
use App\Modules\Settings\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 | Real (non-demo) installation: run migrations, then
 |   php artisan sms:install admin@yourschool.in "StrongPassword" --name="Principal"
 | It creates the first School-app admin, the school profile, the CBSE grade scale and the current session.
 | Everything else (classes, staff, students, fees...) is then created from the School Admin app.
 */
Artisan::command('sms:install {email} {password} {--name=Prasanjeet Kumar Paul} {--school=DAV Public School, Dugda} {--address=Dugda Coal Washery (BCCL), P.O. Dugda, Distt. Bokaro, Jharkhand 828404}', function (string $email, string $password) {
    $admin = User::updateOrCreate(['email' => $email], [
        'name' => $this->option('name'), 'role' => User::ADMIN, 'password' => $password, 'is_active' => true,
    ]);
    foreach (['school_name' => $this->option('school'), 'school_short_name' => 'DAV Dugda', 'address' => $this->option('address'),
        'principal_name' => $this->option('name'), 'principal' => $this->option('name'), 'board' => 'CBSE', 'affiliation' => 'CBSE, New Delhi',
        'affiliation_no' => '3430146', 'receipt_prefix' => 'RCPT', 'library_fine_per_day' => '2',
        'library_issue_days' => '14', 'late_fine_per_day' => '10', 'logo' => \Database\Seeders\DatabaseSeeder::installLogo()] as $k => $v) {
        if (Setting::get($k) === null) Setting::put($k, $v);
    }
    if (Grade::count() === 0) {
        foreach ([['A1', 91, 100, 10, 'Outstanding'], ['A2', 81, 90.99, 9, 'Excellent'], ['B1', 71, 80.99, 8, 'Very good'], ['B2', 61, 70.99, 7, 'Good'],
            ['C1', 51, 60.99, 6, 'Above average'], ['C2', 41, 50.99, 5, 'Average'], ['D', 33, 40.99, 4, 'Pass'], ['E', 0, 32.99, 0, 'Needs improvement']] as [$n, $a, $b, $p, $r]) {
            Grade::create(['name' => $n, 'min_percent' => $a, 'max_percent' => $b, 'point' => $p, 'remark' => $r]);
        }
    }
    if (AcademicYear::count() === 0) {
        $y = now()->month >= 4 ? now()->year : now()->year - 1;
        AcademicYear::create(['title' => $y.'-'.substr((string) ($y + 1), 2), 'start_date' => "$y-04-01", 'end_date' => ($y + 1).'-03-31', 'is_current' => true]);
    }
    if (class_exists(\Website\Database\WebsiteSeeder::class)) {
        $this->call('db:seed', ['--class' => \Website\Database\WebsiteSeeder::class, '--force' => true]);
        $this->info('Public website content + website admin login created (see website/README.md).');
    }
    $this->info("Admin ready: {$admin->email}. Sign in to the School Admin app with this email and password.");
})->purpose('Create the first admin, school profile, grade scale and session for a real installation');
