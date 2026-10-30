<?php

namespace Website\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Describes one editable content type in the CMS: its model, form fields and list columns.
 * The generic CMS controller turns this into list / add / edit / delete screens.
 */
abstract class Resource
{
    public static string $key;          // URL key, e.g. "notices"
    public static string $label;        // menu label, e.g. "Notices & News"
    public static string $singular;     // e.g. "notice"
    public static string $model;
    public static string $icon = 'file-text';
    public static string $group = 'Content';
    public static array $searchable = ['title'];
    public static string $orderBy = 'sort';
    public static string $orderDir = 'asc';
    public static bool $canCreate = true;
    public static bool $canDelete = true;
    public static ?string $help = null;

    abstract public static function fields(): array;

    /** [field => label] shown in the list table. */
    abstract public static function columns(): array;

    public static function title(Model $m): string
    {
        return (string) ($m->title ?? $m->name ?? $m->label ?? ('#'.$m->getKey()));
    }

    public static function publicUrl(Model $m): ?string
    {
        return null;
    }

    public static function query()
    {
        return (static::$model)::query();
    }

    /** Can this particular record be deleted? */
    public static function deletable(Model $m): bool
    {
        return static::$canDelete;
    }

    /** Hook after saving (e.g. fix slugs, clear caches). */
    public static function saved(Model $m): void
    {
    }

    public static function filters(): array
    {
        return [];
    }
}
