<?php

namespace Website\Modules\Menus;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class MenuResource extends Resource
{

    public static string $key = 'menus';
    public static string $label = 'Menus';
    public static string $singular = 'menu link';
    public static string $model = MenuItem::class;
    public static string $icon = 'list';
    public static string $group = 'Website';
    public static array $searchable = ['label', 'url'];
    public static ?string $help = 'The links in the top menu and footer. To make a drop-down, choose a "Parent" link.';

    public static function fields(): array
    {
        $parents = ['' => '— none (top level) —'] + MenuItem::query()->whereNull('parent_id')->where('location', 'header')->orderBy('sort')->pluck('label', 'id')->all();

        return [
            F::text('label', 'Link text', ['required' => true]),
            F::text('url', 'Link (e.g. /about or https://…)', ['required' => true]),
            F::select('location', 'Where', ['header' => 'Top menu', 'footer' => 'Footer quick links']),
            F::select('parent_id', 'Parent (for drop-down)', $parents),
            F::sort(),
            F::toggle('is_visible', 'Visible', ['default' => true]),
            F::toggle('new_tab', 'Open in new tab'),
        ];
    }

    public static function columns(): array
    {
        return ['label' => 'Link', 'url' => 'Goes to', 'location' => 'Where', 'parent.label' => 'Parent', 'sort' => 'Order', 'is_visible' => 'Visible'];
    }

    public static function query()
    {
        return MenuItem::query()->with('parent')->orderBy('location')->orderByRaw('COALESCE(parent_id, id)')->orderByRaw('parent_id IS NOT NULL');
    }

    public static function title(Model $m): string
    {
        return (string) $m->label;
    }

}
