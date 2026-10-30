<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public website + Website CMS tables. Every table is prefixed `site_` so it never
 * collides with the school-management tables that live in the same database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_admins', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email', 150)->unique();
            $t->string('password');
            $t->string('role', 20)->default('editor'); // owner | editor
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 80)->unique();
            $t->text('value')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 120)->unique();
            $t->string('title', 200);
            $t->string('subtitle', 300)->nullable();
            $t->longText('body')->nullable();
            $t->string('image')->nullable();
            $t->string('meta_description', 300)->nullable();
            $t->boolean('is_published')->default(true);
            $t->boolean('is_system')->default(false); // built-in pages cannot be deleted
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_menu_items', function (Blueprint $t) {
            $t->id();
            $t->string('location', 20)->default('header'); // header | footer
            $t->string('label', 80);
            $t->string('url', 300);
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_visible')->default(true);
            $t->boolean('new_tab')->default(false);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_banners', function (Blueprint $t) {
            $t->id();
            $t->string('eyebrow', 120)->nullable();
            $t->string('title', 200);
            $t->string('subtitle', 400)->nullable();
            $t->string('image');
            $t->string('button_text', 60)->nullable();
            $t->string('button_url', 300)->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_stats', function (Blueprint $t) {
            $t->id();
            $t->string('label', 80);
            $t->integer('value')->default(0);
            $t->string('suffix', 10)->nullable();
            $t->string('icon', 40)->nullable();
            $t->integer('sort')->default(0);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_highlights', function (Blueprint $t) {
            $t->id();
            $t->string('title', 120);
            $t->string('summary', 400)->nullable();
            $t->string('icon', 40)->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_notices', function (Blueprint $t) {
            $t->id();
            $t->string('title', 250);
            $t->string('slug', 270)->unique();
            $t->string('category', 30)->default('notice'); // notice | news | circular
            $t->longText('body')->nullable();
            $t->string('image')->nullable();
            $t->string('attachment')->nullable();
            $t->boolean('is_pinned')->default(false);
            $t->boolean('in_ticker')->default(true);
            $t->date('published_at')->nullable();
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_events', function (Blueprint $t) {
            $t->id();
            $t->string('title', 250);
            $t->string('slug', 270)->unique();
            $t->longText('description')->nullable();
            $t->string('venue', 200)->nullable();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_albums', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('slug', 220)->unique();
            $t->string('description', 500)->nullable();
            $t->string('cover')->nullable();
            $t->date('event_date')->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('album_id')->constrained('site_albums')->cascadeOnDelete();
            $t->string('image');
            $t->string('caption', 250)->nullable();
            $t->integer('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('site_people', function (Blueprint $t) {
            $t->id();
            $t->string('group', 20)->default('teaching'); // management | teaching | office
            $t->string('name', 150);
            $t->string('designation', 150)->nullable();
            $t->string('department', 100)->nullable();
            $t->string('qualification', 200)->nullable();
            $t->string('experience', 60)->nullable();
            $t->string('photo')->nullable();
            $t->string('email', 150)->nullable();
            $t->text('bio')->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_achievements', function (Blueprint $t) {
            $t->id();
            $t->string('category', 30)->default('board'); // board | olympiad | sports | cultural | other
            $t->string('title', 200);
            $t->string('student_name', 150)->nullable();
            $t->string('class_name', 40)->nullable();
            $t->string('score', 40)->nullable();
            $t->string('year', 20)->nullable();
            $t->text('description')->nullable();
            $t->string('photo')->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_downloads', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('category', 40)->default('forms'); // prospectus | forms | fees | circulars | other
            $t->string('file')->nullable();
            $t->string('description', 300)->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_testimonials', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('role', 120)->nullable();
            $t->text('quote');
            $t->string('photo')->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_facilities', function (Blueprint $t) {
            $t->id();
            $t->string('title', 150);
            $t->string('slug', 170)->unique();
            $t->string('icon', 40)->nullable();
            $t->string('summary', 400)->nullable();
            $t->longText('body')->nullable();
            $t->string('image')->nullable();
            $t->integer('sort')->default(0);
            $t->boolean('is_published')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_disclosures', function (Blueprint $t) {
            $t->id();
            $t->string('section', 80)->default('General Information');
            $t->string('label', 250);
            $t->text('value')->nullable();
            $t->string('document')->nullable();
            $t->integer('sort')->default(0);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_vacancies', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('department', 100)->nullable();
            $t->string('qualification', 300)->nullable();
            $t->string('experience', 100)->nullable();
            $t->integer('positions')->default(1);
            $t->date('last_date')->nullable();
            $t->longText('description')->nullable();
            $t->boolean('is_open')->default(true);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('site_enquiries', function (Blueprint $t) {
            $t->id();
            $t->string('student_name', 120);
            $t->string('parent_name', 120)->nullable();
            $t->string('phone', 20);
            $t->string('email', 150)->nullable();
            $t->string('class_sought', 40)->nullable();
            $t->date('dob')->nullable();
            $t->string('previous_school', 150)->nullable();
            $t->text('message')->nullable();
            $t->string('status', 20)->default('new'); // new | handled
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('handled_by')->nullable();
            $t->timestamp('handled_at')->nullable();
            $t->unsignedBigInteger('school_enquiry_id')->nullable(); // row in the management `enquiries` table
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });

        Schema::create('site_messages', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email', 150)->nullable();
            $t->string('phone', 20)->nullable();
            $t->string('subject', 200)->nullable();
            $t->text('message');
            $t->string('status', 20)->default('new'); // new | handled
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('handled_by')->nullable();
            $t->timestamp('handled_at')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });

        Schema::create('site_change_log', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('admin_id')->nullable();
            $t->string('admin_name', 120)->nullable();
            $t->string('action', 20);       // created | updated | deleted | login | ...
            $t->string('module', 40);
            $t->unsignedBigInteger('record_id')->nullable();
            $t->string('record_title', 250)->nullable();
            $t->json('changes')->nullable(); // {field: [old, new]}
            $t->timestamp('created_at')->useCurrent();
            $t->index(['module', 'record_id']);
        });
    }

    public function down(): void
    {
        foreach (['site_change_log', 'site_messages', 'site_enquiries', 'site_vacancies', 'site_disclosures', 'site_facilities',
            'site_testimonials', 'site_downloads', 'site_achievements', 'site_people', 'site_photos', 'site_albums', 'site_events',
            'site_notices', 'site_highlights', 'site_stats', 'site_banners', 'site_menu_items', 'site_pages', 'site_settings', 'site_admins'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
