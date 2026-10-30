<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Class XI–XII sections can be marked Science or Commerce, so pickers read e.g. "Class 11 – A (Science)". */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sections', 'stream')) {
            Schema::table('sections', function (Blueprint $t) { $t->string('stream', 30)->nullable()->after('name'); });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sections', 'stream')) {
            Schema::table('sections', function (Blueprint $t) { $t->dropColumn('stream'); });
        }
    }
};
