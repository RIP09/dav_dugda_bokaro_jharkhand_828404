<?php

namespace App\Modules\Reports\Http;

use App\Core\Modules\ModuleRegistry;
use App\Core\Modules\RecordsController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Export any module to Excel/CSV/PDF and import offline registers from Excel/CSV (with a dry-run preview). */
class DataController extends Controller
{
    private function def(Request $request, string $module, string $perm): array
    {
        $def = ModuleRegistry::get($module) ?? abort(404, 'Unknown module');
        abort_unless(ModuleRegistry::can($def, $request->user(), $perm), 403, 'Not allowed to '.$perm.' '.$def['title']);
        return $def;
    }

    public function export(Request $request, string $module)
    {
        $def = $this->def($request, $module, 'export');
        $u = $request->user();
        $q = RecordsController::baseQuery($def, $u);
        RecordsController::applySearch($q, $def, (string) $request->query('q', ''));
        RecordsController::applyFilters($q, $def, (array) $request->query('filter', []));
        $models = $q->orderBy($q->getModel()->getTable().'.id')->limit(20000)->get();
        $rows = RecordsController::rows($def, $models, $u);
        $cols = collect($def['fields'])->reject(fn ($f) => $f['type'] === 'json' || ! empty($f['write_only']))->values();
        if ($request->query('columns') === 'list') $cols = $cols->filter(fn ($f) => $f['list'])->values();
        $flat = array_map(function ($r) use ($cols) {
            $o = [];
            foreach ($cols as $f) {
                $v = $f['type'] === 'ref' ? ($r[$f['name'].'_label'] ?? $r[$f['name']]) : ($r[$f['name']] ?? null);
                if (is_bool($v)) $v = $v ? 'Yes' : 'No';
                if ($f['type'] === 'select' && $v !== null) { $opt = collect($f['options'])->firstWhere('value', $v); $v = $opt['label'] ?? $v; }
                $o[$f['name']] = $v;
            }
            return $o;
        }, $rows);
        $format = $request->query('format', 'xlsx');
        $name = 'DAV_'.$module.'_'.now()->format('Y-m-d');
        if ($format === 'pdf') {
            $pdfCols = $cols->filter(fn ($f) => $f['list'])->values()->all() ?: $cols->take(8)->all();
            return PdfController::render('table', ['title' => $def['title'], 'columns' => $pdfCols, 'rows' => $flat], $name.'.pdf', 'a4', count($pdfCols) > 7 ? 'landscape' : 'portrait');
        }
        if ($format === 'csv') {
            return response()->streamDownload(function () use ($cols, $flat) {
                $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $cols->pluck('label')->all());
                foreach ($flat as $r) fputcsv($out, array_values($r));
                fclose($out);
            }, $name.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        return $this->xlsx($def['title'], $cols->pluck('label')->all(), array_map('array_values', $flat), $name.'.xlsx');
    }

    private function xlsx(string $title, array $headers, array $rows, string $filename, array $notes = [])
    {
        $book = new Spreadsheet();
        $sh = $book->getActiveSheet();
        $sh->setTitle(mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', $title) ?: 'Sheet', 0, 30));
        $sh->fromArray($headers, null, 'A1');
        if ($rows) $sh->fromArray($rows, null, 'A2', true);
        $last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(1, count($headers)));
        $sh->getStyle("A1:{$last}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sh->getStyle("A1:{$last}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B2A6F');
        foreach (range(1, count($headers)) as $i) $sh->getColumnDimensionByColumn($i)->setAutoSize(true);
        $sh->freezePane('A2');
        if ($notes) {
            $n = $book->createSheet(); $n->setTitle('How to fill');
            foreach ($notes as $i => $line) $n->setCellValue('A'.($i + 1), $line);
            $n->getColumnDimension('A')->setWidth(120);
        }
        $book->setActiveSheetIndex(0);
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        IOFactory::createWriter($book, 'Xlsx')->save($tmp);
        return response()->download($tmp, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend();
    }

    private function importFields(array $def): array
    {
        return array_values(array_filter($def['fields'], fn ($f) => ! $f['readonly'] && ! in_array($f['type'], ['json', 'file', 'image'], true) && empty($f['write_only']) && ! (! empty($f['virtual']) && isset($f['get']) && empty($f['write'])) ));
    }

    public function template(Request $request, string $module)
    {
        $def = $this->def($request, $module, 'import');
        $fields = $this->importFields($def);
        $notes = ['Fill one row per record under the coloured header. Do not change the header row.', 'Required columns: '.collect($fields)->where('required', true)->pluck('label')->implode(', '), ''];
        foreach ($fields as $f) {
            $line = $f['label'].($f['required'] ? ' (required)' : '').': ';
            $line .= match ($f['type']) {
                'date' => 'date as YYYY-MM-DD (e.g. 2026-04-01) or DD-MM-YYYY',
                'select' => 'one of: '.collect($f['options'])->pluck('value')->implode(', '),
                'ref' => 'name exactly as shown in the app (e.g. '.($f['ref']['module'] === 'sections' ? 'Class 5-A' : ($f['ref']['module'] === 'classes' ? 'Class 5' : 'the name')).') or its ID number',
                'bool' => 'Yes or No', 'money', 'number' => 'number only, no ₹ or commas', default => 'text',
            };
            if ($f['help']) $line .= ' — '.$f['help'];
            $notes[] = $line;
        }
        if ($def['import_key']) $notes[] = "\nIf a row's ".collect($def['fields'])->firstWhere('name', $def['import_key'])['label'].' already exists, that record is UPDATED instead of added.';
        $headers = array_map(fn ($f) => $f['label'], $fields);
        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($headers) { $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, $headers); fclose($o); }, 'DAV_import_'.$module.'.csv', ['Content-Type' => 'text/csv']);
        }
        return $this->xlsx($def['title'], $headers, [], 'DAV_import_'.$module.'.xlsx', $notes);
    }

    public function import(Request $request, string $module)
    {
        $def = $this->def($request, $module, 'import');
        $request->validate(['file' => 'required|file|max:10240|mimes:xlsx,xls,csv,txt,ods']);
        $dry = filter_var($request->input('dry_run', false), FILTER_VALIDATE_BOOLEAN);
        $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        $sheet = array_values(array_filter($sheet, fn ($r) => count(array_filter($r, fn ($v) => $v !== null && trim((string) $v) !== '')) > 0));
        if (count($sheet) < 2) return $this->fail('The file has no data rows under the header.');
        $fields = collect($this->importFields($def));
        $byHeader = [];
        foreach ($sheet[0] as $i => $h) {
            $h = mb_strtolower(trim((string) $h));
            $f = $fields->first(fn ($f) => mb_strtolower($f['label']) === $h || $f['name'] === $h || str_replace(' ', '_', $h) === $f['name']);
            if ($f) $byHeader[$i] = $f;
        }
        if (! $byHeader) return $this->fail('None of the column headers match. Download the template and keep its header row.');
        $refMaps = [];
        $created = 0; $updated = 0; $errors = []; $preview = [];
        $ctrl = app(RecordsController::class);
        DB::beginTransaction();
        try {
            foreach (array_slice($sheet, 1) as $n => $row) {
                $rowNo = $n + 2; $data = [];
                try {
                    foreach ($byHeader as $i => $f) {
                        $v = $row[$i] ?? null;
                        if (is_string($v)) $v = trim($v);
                        if ($v === '' || $v === null) continue;
                        $data[$f['name']] = $this->coerce($f, $v, $refMaps, $rowNo);
                    }
                    $existing = null;
                    if ($def['import_key'] && ! empty($data[$def['import_key']])) {
                        $kf = collect($def['fields'])->firstWhere('name', $def['import_key']);
                        $q = RecordsController::baseQuery($def, $request->user());
                        if (! empty($kf['on'])) $q->whereHas($kf['on'], fn ($r) => $r->where($def['import_key'], $data[$def['import_key']]));
                        else $q->where($q->getModel()->getTable().'.'.$def['import_key'], $data[$def['import_key']]);
                        $existing = $q->first();
                    }
                    $sub = Request::create('/', $existing ? 'PUT' : 'POST', $existing ? array_diff_key($data, array_flip(array_filter([$def['import_key']]))) : $data);
                    $sub->setUserResolver(fn () => $request->user());
                    DB::transaction(fn () => $existing ? $ctrl->update($sub, $module, $existing->getKey()) : $ctrl->store($sub, $module));
                    $existing ? $updated++ : $created++;
                    if (count($preview) < 10) $preview[] = ['row' => $rowNo, 'action' => $existing ? 'update' : 'create', 'data' => $data];
                } catch (ValidationException $e) {
                    $errors[] = ['row' => $rowNo, 'message' => collect($e->errors())->flatten()->implode(' ')];
                } catch (HttpException $e) {
                    $errors[] = ['row' => $rowNo, 'message' => $e->getMessage()];
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $rowNo, 'message' => mb_substr($e->getMessage(), 0, 200)];
                }
            }
            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) { DB::rollBack(); throw $e; }
        $msg = $dry ? "Preview: {$created} would be added, {$updated} updated, ".count($errors).' rows have problems. Nothing saved yet.'
                    : "Imported: {$created} added, {$updated} updated, ".count($errors).' rows skipped.';
        return $this->ok(['dry_run' => $dry, 'created' => $created, 'updated' => $updated, 'errors' => $errors, 'preview' => $preview], $msg);
    }

    /** Lower-case, any dash (- – —) with or without spaces → "-", single spaces. */
    private static function refKey(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/\s*[-–—]\s*/u', '-', $s);
        return preg_replace('/\s+/u', ' ', $s);
    }

    private function coerce(array $f, $v, array &$refMaps, int $rowNo)
    {
        switch ($f['type']) {
            case 'date':
                if (is_numeric($v)) return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d');
                foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'Y/m/d'] as $fmt) { $d = \DateTime::createFromFormat('!'.$fmt, $v); if ($d) return $d->format('Y-m-d'); }
                return $v;
            case 'bool': return in_array(mb_strtolower((string) $v), ['1', 'yes', 'y', 'true', 'haan', 'ha'], true);
            case 'money': case 'number': return is_numeric($v) ? $v + 0 : (float) preg_replace('/[^0-9.\-]/', '', (string) $v);
            case 'select':
                $opt = collect($f['options'])->first(fn ($o) => mb_strtolower((string) $o['value']) === mb_strtolower((string) $v) || mb_strtolower($o['label']) === mb_strtolower((string) $v));
                return $opt['value'] ?? $v;
            case 'ref':
                if (is_numeric($v)) return (int) $v;
                $mod = $f['ref']['module'];
                if (! isset($refMaps[$mod])) {
                    $rd = ModuleRegistry::get($mod);
                    // Names in a sheet may be "Class 8 – A", "Class 8 - A", "Class 8-A" or "Class 8 – A (Science)": all match.
                    $refMaps[$mod] = [];
                    foreach ($rd['model']::query()->with($rd['with'])->get() as $m) {
                        $aliases = [ModuleRegistry::titleFor($mod, $m), ModuleRegistry::optionFor($mod, $m)];
                        if ($m instanceof \App\Modules\Academics\Models\Section) {
                            $aliases[] = \App\Modules\Academics\Models\Section::labelFor($m->schoolClass?->name, $m->name);
                            $aliases[] = ($m->schoolClass?->name ?? '').'-'.$m->name;
                        }
                        foreach ($aliases as $a) $refMaps[$mod][self::refKey($a)] ??= $m->getKey();
                    }
                }
                $key = self::refKey((string) $v);
                if (isset($refMaps[$mod][$key])) return $refMaps[$mod][$key];
                throw ValidationException::withMessages([$f['name'] => "“{$v}” not found for {$f['label']}."]);
            case 'phone': return is_float($v) ? number_format($v, 0, '', '') : (string) $v;
            default: return is_float($v) && floor($v) == $v ? (string) (int) $v : (string) $v;
        }
    }
}
