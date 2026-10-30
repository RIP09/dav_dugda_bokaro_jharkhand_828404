<?php

namespace Website\Modules\Disclosure;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class DisclosureResource extends Resource
{

    public static string $key = 'disclosure';
    public static string $label = 'Public Disclosure (CBSE)';
    public static string $singular = 'disclosure row';
    public static string $model = Disclosure::class;
    public static string $icon = 'clipboard-list';
    public static array $searchable = ['label', 'value', 'section'];
    public static ?string $help = 'The Mandatory Public Disclosure page CBSE asks every school to publish. Fill in each value and upload the certificates as PDFs.';

    public static function fields(): array
    {
        return [
            F::select('section', 'Section', [
                'General Information' => 'A. General Information',
                'Documents and Information' => 'B. Documents and Information',
                'Result and Academics' => 'C. Result and Academics',
                'Staff (Teaching)' => 'D. Staff (Teaching)',
                'School Infrastructure' => 'E. School Infrastructure',
            ]),
            F::text('label', 'Information', ['required' => true, 'wide' => true]),
            F::textarea('value', 'Details'),
            F::file('document', 'Document (PDF, optional)'),
            F::sort(),
        ];
    }

    public static function columns(): array
    {
        return ['section' => 'Section', 'label' => 'Information', 'value' => 'Details', 'document' => 'Document'];
    }

    public static function title(Model $m): string
    {
        return (string) $m->label;
    }

}
