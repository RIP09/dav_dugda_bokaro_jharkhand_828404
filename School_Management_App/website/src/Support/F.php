<?php

namespace Website\Support;

/** Field definitions for CMS forms (kept as plain arrays so they are easy to read and change). */
class F
{
    public static function make(string $type, string $name, string $label, array $extra = []): array
    {
        return array_merge(['type' => $type, 'name' => $name, 'label' => $label, 'required' => false, 'help' => null, 'wide' => false], $extra);
    }

    public static function text($n, $l, $x = []) { return self::make('text', $n, $l, $x); }
    public static function textarea($n, $l, $x = []) { return self::make('textarea', $n, $l, $x + ['wide' => true]); }
    public static function rich($n, $l, $x = []) { return self::make('rich', $n, $l, $x + ['wide' => true]); }
    public static function number($n, $l, $x = []) { return self::make('number', $n, $l, $x); }
    public static function date($n, $l, $x = []) { return self::make('date', $n, $l, $x); }
    public static function datetime($n, $l, $x = []) { return self::make('datetime', $n, $l, $x); }
    public static function select($n, $l, array $options, $x = []) { return self::make('select', $n, $l, $x + ['options' => $options]); }
    public static function toggle($n, $l, $x = []) { return self::make('toggle', $n, $l, $x); }
    public static function image($n, $l, $x = []) { return self::make('image', $n, $l, $x); }
    public static function file($n, $l, $x = []) { return self::make('file', $n, $l, $x); }
    public static function url($n, $l, $x = []) { return self::make('url', $n, $l, $x); }
    public static function email($n, $l, $x = []) { return self::make('email', $n, $l, $x); }
    public static function slug($n, $l, $from = 'title', $x = []) { return self::make('slug', $n, $l, $x + ['from' => $from, 'help' => 'Part of the web address. Leave empty to make it from the title.']); }
    public static function icon($n, $l, $x = []) { return self::make('icon', $n, $l, $x + ['options' => Icons::choices()]); }
    public static function sort($x = []) { return self::make('number', 'sort', 'Order', $x + ['help' => 'Smaller numbers show first.', 'default' => 0]); }
}
