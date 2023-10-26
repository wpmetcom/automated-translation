<?php

namespace Wpmetcom\AutomatedTranslation\Services;

class Helper
{
    /**
     * for custom styling.
     */
    public static function trim(string $str): string
    {
        return trim(trim($str), "'\"");
    }
}
