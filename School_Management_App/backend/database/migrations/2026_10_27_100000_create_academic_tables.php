<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Academic structure: sessions, classes, sections, subjects, settings.
 * Modeled on hrshadhin/school-management-system (academic_years, i_classes, sections, subjects)
 * and 4jean/lav_sms (my_classes, class_types, sections, subjects, settings).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('title', 50);           // e.g. 2026-27
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);            // e.g. Class 5, LKG
            $table->unsignedSmallInteger('numeric_order')->default(0);
            $table->string('group', 30)->nullable(); // Pre-primary / Primary / Secondary / Senior Secondary
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('name', 20);
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete(); // class teacher
            $table->string('room', 30)->nullable();
            $table->timestamps();
            $table->unique(['class_id', 'name']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 20)->nullable();
            $table->string('type', 20)->default('theory'); // theory | practical | both
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('settings');
    }
};
