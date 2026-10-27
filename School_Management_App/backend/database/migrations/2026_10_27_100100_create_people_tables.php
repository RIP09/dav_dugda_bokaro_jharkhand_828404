<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Students (with parent link, like lav_sms student_records.my_parent_id), staff profiles, transport. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('vehicle_no', 30)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->decimal('fare', 10, 2)->default(0);
            $table->json('stops')->nullable();
            $table->string('pickup_time', 10)->nullable();
            $table->string('drop_time', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('admission_no', 30)->unique();
            $table->unsignedInteger('roll_no')->nullable();
            $table->foreignId('class_id')->constrained('school_classes');
            $table->foreignId('section_id')->constrained('sections');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transport_route_id')->nullable()->constrained('transport_routes')->nullOnDelete();
            $table->date('admission_date')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('religion', 30)->nullable();
            $table->string('category', 20)->nullable(); // General / OBC / SC / ST
            $table->string('aadhaar_no', 20)->nullable();
            $table->string('father_name', 100)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('house', 30)->nullable();
            $table->string('status', 20)->default('active'); // active | left | graduated
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('employee_id', 30)->unique();
            $table->string('designation', 80)->nullable();
            $table->string('department', 80)->nullable();
            $table->date('joining_date')->nullable();
            $table->string('qualification', 120)->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('students');
        Schema::dropIfExists('transport_routes');
    }
};
