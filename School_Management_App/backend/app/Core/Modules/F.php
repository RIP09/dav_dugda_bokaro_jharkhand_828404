<?php

namespace App\Core\Modules;

/** Small helpers to declare module fields compactly in app/Modules/<Module>/module.php. */
class F
{
    public static function make(string $name, string $label, string $type = 'text', array $o = []): array
    {
        return array_merge([
            'name' => $name, 'label' => $label, 'type' => $type, 'required' => false, 'list' => false, 'filter' => false,
            'readonly' => false, 'immutable' => false, 'options' => null, 'ref' => null, 'help' => null, 'default' => null,
        ], $o);
    }

    public static function text($n, $l, $o = []) { return self::make($n, $l, 'text', $o); }
    public static function textarea($n, $l, $o = []) { return self::make($n, $l, 'textarea', $o); }
    public static function number($n, $l, $o = []) { return self::make($n, $l, 'number', $o); }
    public static function money($n, $l, $o = []) { return self::make($n, $l, 'money', $o); }
    public static function date($n, $l, $o = []) { return self::make($n, $l, 'date', $o); }
    public static function time($n, $l, $o = []) { return self::make($n, $l, 'time', $o); }
    public static function datetime($n, $l, $o = []) { return self::make($n, $l, 'datetime', $o); }
    public static function bool($n, $l, $o = []) { return self::make($n, $l, 'bool', $o); }
    public static function email($n, $l, $o = []) { return self::make($n, $l, 'email', $o); }
    public static function phone($n, $l, $o = []) { return self::make($n, $l, 'phone', $o); }
    public static function file($n, $l, $o = []) { return self::make($n, $l, 'file', $o); }
    public static function image($n, $l, $o = []) { return self::make($n, $l, 'image', $o); }
    public static function json($n, $l, $o = []) { return self::make($n, $l, 'json', array_merge(['readonly' => true], $o)); }

    /** select: $options = ['value' => 'Label', ...] or list of values */
    public static function select($n, $l, array $options, $o = [])
    {
        $opts = [];
        foreach ($options as $k => $v) $opts[] = is_int($k) ? ['value' => $v, 'label' => ucwords(str_replace('_', ' ', $v))] : ['value' => $k, 'label' => $v];
        return self::make($n, $l, 'select', array_merge(['options' => $opts], $o));
    }

    public static function ref($n, $l, string $module, $o = [])
    {
        return self::make($n, $l, 'ref', array_merge(['ref' => ['module' => $module]], $o));
    }
}
