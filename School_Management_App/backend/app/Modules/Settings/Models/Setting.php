<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];

    public static function get(string $key, $default = null) { return static::where('key', $key)->value('value') ?? $default; }
    public static function put(string $key, $value): void { static::updateOrCreate(['key' => $key], ['value' => $value]); }
    public static function allAsArray(): array { return static::pluck('value', 'key')->all(); }
}
