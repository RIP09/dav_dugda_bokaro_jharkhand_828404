<?php

use App\Modules\Academics\Http\AcademicController;
use App\Modules\Attendance\Http\AttendanceController;
use App\Modules\Auth\Http\AuthController;
use App\Modules\Dashboard\Http\DashboardController;
use App\Modules\Exams\Http\ExamController;
use App\Modules\Fees\Http\FeeController;
use App\Modules\Homework\Http\HomeworkController;
use App\Modules\Leave\Http\LeaveController;
use App\Modules\Library\Http\LibraryController;
use App\Modules\Communication\Http\MessageController;
use App\Modules\Communication\Http\NoticeController;
use App\Modules\Students\Http\StudentController;
use App\Modules\Timetable\Http\TimetableController;
use App\Modules\Transport\Http\TransportController;
use App\Modules\Staff\Http\UserController;
use Illuminate\Support\Facades\Route;

/*
| DAV School Management System - REST API v1
| Roles: admin (School app) | teacher, accountant, librarian, staff (Staff app) | parent (Parent app) | student (Student app)
*/

Route::prefix('v1')->group(function () {
    Route::get('ping', fn () => ['data' => ['status' => 'ok', 'app' => config('app.name'), 'time' => now()->toIso8601String()]]);
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('school', [AcademicController::class, 'settings']);

    Route::middleware('auth:sanctum')->group(function () {
        $all = 'role:admin,teacher,accountant,librarian,staff,parent,student';
        $staff = 'role:admin,teacher,accountant,librarian,staff';
        $admin = 'role:admin';

        // ---- Everyone signed in -----------------------------------------------------------------
        Route::middleware($all)->group(function () {
            Route::get('auth/me', [AuthController::class, 'me']);
            Route::post('auth/logout', [AuthController::class, 'logout']);
            Route::post('auth/change-password', [AuthController::class, 'changePassword']);
            Route::put('auth/profile', [AuthController::class, 'updateProfile']);
            Route::get('dashboard', DashboardController::class);

            Route::get('classes', [AcademicController::class, 'classes']);
            Route::get('sections', [AcademicController::class, 'sections']);
            Route::get('subjects', [AcademicController::class, 'subjects']);
            Route::get('academic-years', [AcademicController::class, 'years']);

            Route::get('students', [StudentController::class, 'index']);
            Route::get('students/{student}', [StudentController::class, 'show']);
            Route::get('students/{student}/attendance', [AttendanceController::class, 'studentReport']);
            Route::get('students/{student}/report-card', [ExamController::class, 'reportCard']);
            Route::get('attendance/my', [AttendanceController::class, 'my']);
            Route::get('report-card/my', [ExamController::class, 'myReportCard']);

            Route::get('timetable', [TimetableController::class, 'index']);
            Route::get('exams', [ExamController::class, 'index']);
            Route::get('exams/{exam}/schedules', [ExamController::class, 'schedules']);
            Route::get('grades', [ExamController::class, 'grades']);

            Route::get('invoices', [FeeController::class, 'invoices']);
            Route::get('payments', [FeeController::class, 'payments']);
            Route::get('fee-types', [FeeController::class, 'types']);

            Route::get('homework', [HomeworkController::class, 'index']);
            Route::post('homework/{homework}/submit', [HomeworkController::class, 'submit'])->middleware('role:student');

            Route::get('notices', [NoticeController::class, 'index']);
            Route::get('events', [NoticeController::class, 'events']);

            Route::get('library/books', [LibraryController::class, 'books']);
            Route::get('library/issues', [LibraryController::class, 'issues']);
            Route::get('transport/routes', [TransportController::class, 'index']);

            Route::get('leaves', [LeaveController::class, 'index']);
            Route::post('leaves', [LeaveController::class, 'store']);
            Route::delete('leaves/{leave}', [LeaveController::class, 'destroy']);

            Route::get('messages', [MessageController::class, 'index']);
            Route::get('messages/unread', [MessageController::class, 'unread']);
            Route::get('messages/contacts', [MessageController::class, 'contacts']);
            Route::post('messages', [MessageController::class, 'store']);
            Route::get('messages/{message}', [MessageController::class, 'read']);
        });

        // ---- Staff app (teachers & other staff) + admin --------------------------------------------
        Route::middleware($staff)->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::get('attendance/students', [AttendanceController::class, 'studentSheet']);
            Route::get('marks', [ExamController::class, 'marksSheet']);
            Route::get('homework/{homework}/submissions', [HomeworkController::class, 'submissions']);
            Route::get('transport/routes/{route}/students', [TransportController::class, 'students']);
        });
        Route::middleware('role:admin,teacher')->group(function () {
            Route::post('attendance/students', [AttendanceController::class, 'storeStudents']);
            Route::post('marks', [ExamController::class, 'storeMarks']);
            Route::post('homework', [HomeworkController::class, 'store']);
            Route::delete('homework/{homework}', [HomeworkController::class, 'destroy']);
            Route::post('submissions/{submission}/review', [HomeworkController::class, 'review']);
            Route::post('notices', [NoticeController::class, 'store']);
            Route::delete('notices/{notice}', [NoticeController::class, 'destroy']);
            Route::post('leaves/{leave}/review', [LeaveController::class, 'review']);
        });
        Route::middleware('role:admin,accountant')->group(function () {
            Route::get('fees/summary', [FeeController::class, 'summary']);
            Route::post('fee-types', [FeeController::class, 'storeType']);
            Route::delete('fee-types/{type}', [FeeController::class, 'destroyType']);
            Route::post('invoices', [FeeController::class, 'storeInvoice']);
            Route::delete('invoices/{invoice}', [FeeController::class, 'destroyInvoice']);
            Route::post('invoices/{invoice}/payments', [FeeController::class, 'pay']);
        });
        Route::middleware('role:admin,librarian')->group(function () {
            Route::post('library/books', [LibraryController::class, 'storeBook']);
            Route::put('library/books/{book}', [LibraryController::class, 'updateBook']);
            Route::delete('library/books/{book}', [LibraryController::class, 'destroyBook']);
            Route::post('library/issues', [LibraryController::class, 'issue']);
            Route::post('library/issues/{issue}/return', [LibraryController::class, 'returnBook']);
        });

        // ---- School app (admin controls everything) -------------------------------------------------
        Route::middleware($admin)->group(function () {
            Route::post('users', [UserController::class, 'store']);
            Route::get('users/{user}', [UserController::class, 'show']);
            Route::put('users/{user}', [UserController::class, 'update']);
            Route::delete('users/{user}', [UserController::class, 'destroy']);
            Route::post('users/{user}/toggle-active', [UserController::class, 'toggle']);

            Route::post('students', [StudentController::class, 'store']);
            Route::put('students/{student}', [StudentController::class, 'update']);
            Route::delete('students/{student}', [StudentController::class, 'destroy']);
            Route::post('students/promote', [StudentController::class, 'promote']);

            Route::post('classes', [AcademicController::class, 'storeClass']);
            Route::put('classes/{class}', [AcademicController::class, 'updateClass']);
            Route::delete('classes/{class}', [AcademicController::class, 'destroyClass']);
            Route::post('sections', [AcademicController::class, 'storeSection']);
            Route::put('sections/{section}', [AcademicController::class, 'updateSection']);
            Route::delete('sections/{section}', [AcademicController::class, 'destroySection']);
            Route::post('subjects', [AcademicController::class, 'storeSubject']);
            Route::put('subjects/{subject}', [AcademicController::class, 'updateSubject']);
            Route::delete('subjects/{subject}', [AcademicController::class, 'destroySubject']);
            Route::post('academic-years', [AcademicController::class, 'storeYear']);
            Route::post('academic-years/{year}/current', [AcademicController::class, 'makeCurrentYear']);
            Route::put('school', [AcademicController::class, 'updateSettings']);

            Route::get('attendance/class-summary', [AttendanceController::class, 'classSummary']);
            Route::get('attendance/staff', [AttendanceController::class, 'staffSheet']);
            Route::post('attendance/staff', [AttendanceController::class, 'storeStaff']);

            Route::post('timetable', [TimetableController::class, 'store']);
            Route::delete('timetable/{entry}', [TimetableController::class, 'destroy']);

            Route::post('exams', [ExamController::class, 'store']);
            Route::put('exams/{exam}', [ExamController::class, 'update']);
            Route::delete('exams/{exam}', [ExamController::class, 'destroy']);
            Route::post('exams/{exam}/publish', [ExamController::class, 'publish']);
            Route::post('exams/{exam}/schedules', [ExamController::class, 'storeSchedule']);
            Route::post('grades', [ExamController::class, 'storeGrade']);
            Route::delete('grades/{grade}', [ExamController::class, 'destroyGrade']);

            Route::post('events', [NoticeController::class, 'storeEvent']);
            Route::delete('events/{event}', [NoticeController::class, 'destroyEvent']);

            Route::post('transport/routes', [TransportController::class, 'store']);
            Route::put('transport/routes/{route}', [TransportController::class, 'update']);
            Route::delete('transport/routes/{route}', [TransportController::class, 'destroy']);
        });
    });
});
