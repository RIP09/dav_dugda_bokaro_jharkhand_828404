<?php

namespace App\Modules\Certificates\Http;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicYear;
use App\Modules\Academics\Models\Section;
use App\Modules\Certificates\Models\Promotion;
use App\Modules\Students\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Session rollover: move students class → next class into the new session, keep a permanent history row per student.
 * Students not mapped (or excluded) are detained; students of the last class can be marked passed out.
 */
class PromotionController extends Controller
{
    public function preview(Request $request)
    {
        $classes = \App\Modules\Academics\Models\SchoolClass::orderBy('numeric_order')->get();
        $map = [];
        foreach ($classes as $i => $c) $map[] = ['from_class_id' => $c->id, 'from_class' => $c->name, 'to_class_id' => $classes[$i + 1]->id ?? null,
            'to_class' => $classes[$i + 1]->name ?? 'Pass out', 'students' => Student::where('class_id', $c->id)->where('status', 'active')->count()];
        return $this->ok(['years' => AcademicYear::orderByDesc('start_date')->get(['id', 'title', 'is_current']), 'mappings' => $map]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'from_academic_year_id' => 'nullable|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id',
            'mappings' => 'required|array|min:1',
            'mappings.*.from_class_id' => 'required|exists:school_classes,id',
            'mappings.*.to_class_id' => 'nullable|exists:school_classes,id',
            'exclude_student_ids' => 'nullable|array',
            'make_current' => 'nullable|boolean',
        ]);
        $exclude = collect($data['exclude_student_ids'] ?? [])->map(fn ($v) => (int) $v)->all();
        $fromYear = $data['from_academic_year_id'] ?? AcademicYear::current()?->id;
        $counts = ['promoted' => 0, 'detained' => 0, 'passed_out' => 0];
        DB::transaction(function () use ($data, $exclude, $fromYear, $request, &$counts) {
            foreach ($data['mappings'] as $map) {
                $students = Student::where('class_id', $map['from_class_id'])->where('status', 'active')->orderBy('section_id')->orderBy('roll_no')->get();
                $rolls = [];
                foreach ($students as $s) {
                    $fromSection = Section::find($s->section_id);
                    $old = ['class' => $s->class_id, 'section' => $s->section_id, 'roll' => $s->roll_no];
                    if (in_array($s->id, $exclude, true)) {
                        $result = 'detained'; $toClass = $s->class_id; $toSection = $s->section_id;
                    } elseif (empty($map['to_class_id'])) {
                        $result = 'passed_out'; $toClass = $s->class_id; $toSection = $s->section_id;
                        $s->update(['status' => 'graduated']);
                    } else {
                        $result = 'promoted'; $toClass = $map['to_class_id'];
                        $toSection = Section::where('class_id', $toClass)->where('name', $fromSection?->name)->value('id') ?? Section::where('class_id', $toClass)->orderBy('name')->value('id');
                    }
                    $newRoll = $result === 'passed_out' ? $s->roll_no : (($rolls[$toSection] ??= (int) Student::where('section_id', $toSection)->whereNotIn('id', $students->pluck('id'))->max('roll_no')) + 1);
                    if ($result !== 'passed_out') { $rolls[$toSection] = $newRoll; $s->update(['class_id' => $toClass, 'section_id' => $toSection, 'roll_no' => $newRoll, 'academic_year_id' => $data['to_academic_year_id']]); }
                    Promotion::create(['student_id' => $s->id, 'from_academic_year_id' => $fromYear, 'to_academic_year_id' => $data['to_academic_year_id'],
                        'from_class_id' => $old['class'], 'from_section_id' => $old['section'], 'to_class_id' => $toClass, 'to_section_id' => $toSection,
                        'old_roll_no' => $old['roll'], 'new_roll_no' => $newRoll, 'result' => $result, 'promoted_by' => $request->user()->id]);
                    $counts[$result]++;
                }
            }
            if ($data['make_current'] ?? true) {
                AcademicYear::query()->update(['is_current' => false]);
                AcademicYear::whereKey($data['to_academic_year_id'])->update(['is_current' => true]);
            }
        });
        return $this->ok($counts, "Done: {$counts['promoted']} promoted, {$counts['detained']} detained, {$counts['passed_out']} passed out. History saved permanently.");
    }
}
