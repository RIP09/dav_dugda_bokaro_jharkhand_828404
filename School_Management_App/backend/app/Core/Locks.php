<?php

namespace App\Core;

/** Central switch for the permanent-record rules (see Concerns\Lockable). */
class Locks
{
    private static int $bypass = 0;

    /** Run system-internal changes (e.g. cancelling a receipt) that are allowed to touch locked records. */
    public static function bypass(callable $fn)
    {
        self::$bypass++;
        try { return $fn(); } finally { self::$bypass--; }
    }

    public static function active(): bool { return self::$bypass === 0; }
}
