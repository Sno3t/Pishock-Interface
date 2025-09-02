<?php


namespace App\Enums;


class Operations
{
    public const SHOCK = 0;

    public const VIBRATE = 1;

    public const BEEP = 2;

    /**
     * @var array|int[]
     */
    public static array $types = [
        self::SHOCK,
        self::VIBRATE,
        self::BEEP,
    ];
}
