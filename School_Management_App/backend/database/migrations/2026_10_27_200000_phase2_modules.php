<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 2: audit trail, admissions, certificates, promotions, fee structures, accounts, payroll,
 *  inventory, hostel, vehicles, front office, complaints, notifications, syllabus, study material. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('user_name', 120)->nullable();
            $t->string('action', 20);              // create | update | delete | cancel | lock | issue ...
            $t->string('module', 60)->index();
            $t->string('table_name', 60)->nullable();
            $t->unsignedBigInteger('record_id')->nullable()->index();
            $t->string('record_title', 200)->nullable();
            $t->json('changes')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->nullable()->index();
        });

        Schema::create('enquiries', function (Blueprint $t) {
            $t->id();
            $t->string('student_name', 120);
            $t->string('parent_name', 120)->nullable();
            $t->string('phone', 20);
            $t->string('email', 120)->nullable();
            $t->string('class_sought', 40)->nullable();
            $t->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->date('dob')->nullable();
            $t->string('gender', 10)->nullable();
            $t->string('previous_school', 150)->nullable();
            $t->string('address', 300)->nullable();
            $t->string('source', 20)->default('walk_in');    // walk_in | phone | website | reference | other
            $t->string('status', 20)->default('new');         // new | contacted | visit | test | approved | admitted | rejected | closed
            $t->date('follow_up_date')->nullable();
            $t->date('test_date')->nullable();
            $t->decimal('test_score', 6, 2)->nullable();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $t->text('message')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('student_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->string('title', 120);
            $t->string('type', 30)->nullable();     // birth_certificate | aadhaar | tc | marksheet | photo | other
            $t->string('file', 255)->nullable();
            $t->string('remarks', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('certificates', function (Blueprint $t) {
            $t->id();
            $t->string('certificate_no', 30)->unique();
            $t->string('type', 20);                  // tc | character | bonafide
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->date('issue_date')->nullable();
            $t->string('status', 12)->default('draft'); // draft | issued | cancelled
            $t->string('purpose', 200)->nullable();
            $t->string('conduct', 40)->nullable();
            $t->date('leaving_date')->nullable();
            $t->string('last_class', 40)->nullable();
            $t->string('reason_for_leaving', 200)->nullable();
            $t->string('remarks', 300)->nullable();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('cancel_reason', 200)->nullable();
            $t->timestamps();
        });

        Schema::create('promotions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('from_academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $t->foreignId('to_academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $t->foreignId('from_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->foreignId('from_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $t->foreignId('to_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->foreignId('to_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $t->unsignedInteger('old_roll_no')->nullable();
            $t->unsignedInteger('new_roll_no')->nullable();
            $t->string('result', 20)->default('promoted'); // promoted | detained | passed_out
            $t->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $t->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->foreignId('fee_type_id')->constrained('fee_types')->cascadeOnDelete();
            $t->decimal('amount', 10, 2);
            $t->string('frequency', 15)->default('monthly'); // monthly | quarterly | half_yearly | yearly | one_time
            $t->unsignedTinyInteger('due_day')->default(10);
            $t->timestamps();
        });

        Schema::create('concessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('fee_type_id')->nullable()->constrained('fee_types')->nullOnDelete();
            $t->string('type', 10)->default('percent'); // percent | fixed
            $t->decimal('value', 10, 2);
            $t->string('reason', 150)->nullable();    // sibling | staff ward | merit | RTE | other
            $t->date('valid_till')->nullable();
            $t->timestamps();
        });

        Schema::table('invoices', function (Blueprint $t) {
            $t->decimal('fine', 10, 2)->default(0);
            $t->string('month', 7)->nullable();
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->string('status', 12)->default('valid'); // valid | cancelled
            $t->timestamp('cancelled_at')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('cancel_reason', 200)->nullable();
            $t->string('remarks', 200)->nullable();
        });
        Schema::table('exams', function (Blueprint $t) {
            $t->boolean('results_locked')->default(false);
            $t->timestamp('locked_at')->nullable();
        });
        Schema::table('book_issues', function (Blueprint $t) {
            $t->boolean('fine_paid')->default(false);
        });
        Schema::table('students', function (Blueprint $t) {
            $t->string('previous_school', 150)->nullable();
            $t->string('mother_tongue', 30)->nullable();
            $t->string('nationality', 30)->nullable()->default('Indian');
        });

        Schema::create('account_heads', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('type', 10);  // income | expense
            $t->string('description', 200)->nullable();
            $t->timestamps();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->string('voucher_no', 30)->unique();
            $t->date('date');
            $t->foreignId('account_head_id')->nullable()->constrained('account_heads')->nullOnDelete();
            $t->string('type', 10);  // income | expense
            $t->decimal('amount', 12, 2);
            $t->string('mode', 15)->default('cash');
            $t->string('party', 120)->nullable();
            $t->string('reference', 100)->nullable();
            $t->string('description', 300)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('salary_structures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            foreach (['basic', 'hra', 'da', 'ta', 'other_allowance', 'pf', 'esi', 'tds', 'other_deduction'] as $c) $t->decimal($c, 10, 2)->default(0);
            $t->string('bank_name', 80)->nullable();
            $t->string('account_no', 30)->nullable();
            $t->string('ifsc', 15)->nullable();
            $t->string('pan', 15)->nullable();
            $t->timestamps();
        });
        Schema::create('payslips', function (Blueprint $t) {
            $t->id();
            $t->string('payslip_no', 30)->unique();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('month', 7);
            $t->unsignedTinyInteger('working_days')->default(26);
            $t->decimal('present_days', 5, 1)->default(26);
            $t->decimal('gross', 10, 2)->default(0);
            $t->decimal('deductions', 10, 2)->default(0);
            $t->decimal('net', 10, 2)->default(0);
            $t->json('breakdown')->nullable();
            $t->string('status', 10)->default('draft'); // draft | paid
            $t->date('paid_on')->nullable();
            $t->string('mode', 15)->nullable();
            $t->string('remarks', 200)->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'month']);
        });

        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('category', 60)->nullable();
            $t->string('unit', 20)->default('pcs');
            $t->decimal('quantity', 10, 2)->default(0);
            $t->decimal('min_quantity', 10, 2)->default(0);
            $t->decimal('unit_price', 10, 2)->nullable();
            $t->string('location', 80)->nullable();
            $t->string('supplier', 120)->nullable();
            $t->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $t->string('type', 5); // in | out
            $t->decimal('quantity', 10, 2);
            $t->date('date');
            $t->string('party', 120)->nullable();   // supplier or issued to
            $t->string('note', 200)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('assets', function (Blueprint $t) {
            $t->id();
            $t->string('asset_tag', 30)->unique();
            $t->string('name', 120);
            $t->string('category', 60)->nullable();
            $t->string('location', 80)->nullable();
            $t->date('purchase_date')->nullable();
            $t->decimal('cost', 12, 2)->nullable();
            $t->string('vendor', 120)->nullable();
            $t->date('warranty_till')->nullable();
            $t->string('condition', 20)->default('good'); // new | good | repair | scrapped
            $t->string('assigned_to', 120)->nullable();
            $t->timestamps();
        });

        Schema::create('hostels', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('type', 10)->default('boys'); // boys | girls | mixed
            $t->string('warden_name', 100)->nullable();
            $t->string('warden_phone', 20)->nullable();
            $t->string('address', 200)->nullable();
            $t->timestamps();
        });
        Schema::create('hostel_rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $t->string('room_no', 20);
            $t->string('room_type', 30)->nullable();
            $t->unsignedSmallInteger('capacity')->default(4);
            $t->decimal('fee_per_month', 10, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('hostel_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('hostel_room_id')->constrained('hostel_rooms')->cascadeOnDelete();
            $t->string('bed_no', 10)->nullable();
            $t->date('from_date');
            $t->date('to_date')->nullable();
            $t->string('status', 10)->default('active'); // active | left
            $t->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->string('vehicle_no', 30)->unique();
            $t->string('model', 80)->nullable();
            $t->unsignedSmallInteger('capacity')->default(40);
            $t->foreignId('transport_route_id')->nullable()->constrained('transport_routes')->nullOnDelete();
            $t->string('driver_name', 100)->nullable();
            $t->string('driver_phone', 20)->nullable();
            $t->string('driver_license', 40)->nullable();
            $t->date('insurance_expiry')->nullable();
            $t->date('fitness_expiry')->nullable();
            $t->date('pollution_expiry')->nullable();
            $t->timestamps();
        });

        Schema::create('visitors', function (Blueprint $t) {
            $t->id();
            $t->string('pass_no', 20)->nullable();
            $t->string('name', 120);
            $t->string('phone', 20)->nullable();
            $t->string('purpose', 150);
            $t->string('whom_to_meet', 120)->nullable();
            $t->unsignedSmallInteger('persons')->default(1);
            $t->string('id_proof', 60)->nullable();
            $t->dateTime('in_time');
            $t->dateTime('out_time')->nullable();
            $t->string('note', 200)->nullable();
            $t->timestamps();
        });
        Schema::create('postal_records', function (Blueprint $t) {
            $t->id();
            $t->string('type', 10); // dispatch | receive
            $t->string('reference_no', 40)->nullable();
            $t->string('title', 150);
            $t->string('from_to', 150)->nullable();
            $t->string('address', 250)->nullable();
            $t->date('date');
            $t->string('note', 200)->nullable();
            $t->timestamps();
        });
        Schema::create('call_logs', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120)->nullable();
            $t->string('phone', 20);
            $t->string('type', 10)->default('incoming');
            $t->string('purpose', 150)->nullable();
            $t->date('date');
            $t->date('follow_up_date')->nullable();
            $t->string('note', 250)->nullable();
            $t->timestamps();
        });
        Schema::create('complaints', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('type', 12)->default('complaint'); // complaint | feedback | suggestion
            $t->string('category', 40)->nullable();
            $t->string('subject', 150);
            $t->text('description')->nullable();
            $t->string('status', 15)->default('open'); // open | in_progress | resolved | closed
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->text('resolution')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('notice_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('notice_id')->constrained('notices')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('read_at');
            $t->unique(['notice_id', 'user_id']);
        });
        Schema::create('app_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('title', 150);
            $t->string('body', 500)->nullable();
            $t->string('module', 40)->nullable();
            $t->unsignedBigInteger('record_id')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'read_at']);
        });
        Schema::create('syllabus', function (Blueprint $t) {
            $t->id();
            $t->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $t->string('title', 150);
            $t->text('description')->nullable();
            $t->string('planned_month', 7)->nullable();
            $t->string('status', 12)->default('pending'); // pending | in_progress | completed
            $t->date('completed_on')->nullable();
            $t->timestamps();
        });
        Schema::create('study_materials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $t->string('title', 150);
            $t->text('description')->nullable();
            $t->string('file', 255)->nullable();
            $t->string('link', 255)->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['study_materials', 'syllabus', 'app_notifications', 'notice_reads', 'complaints', 'call_logs', 'postal_records', 'visitors',
            'vehicles', 'hostel_allocations', 'hostel_rooms', 'hostels', 'assets', 'stock_movements', 'inventory_items', 'payslips',
            'salary_structures', 'transactions', 'account_heads', 'concessions', 'fee_structures', 'promotions', 'certificates',
            'student_documents', 'enquiries', 'audit_logs'] as $t) Schema::dropIfExists($t);
    }
};
