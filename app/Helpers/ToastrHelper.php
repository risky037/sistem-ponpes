<?php

namespace App\Helpers;

class ToastrHelper
{
    public static function success($message, $title = null)
    {
        flash($message, 'success');
    }

    public static function info($message, $title = null)
    {
        flash($message, 'info');
    }

    public static function warning($message, $title = null)
    {
        flash($message, 'warning');
    }

    public static function error($message, $title = null)
    {
        flash($message, 'error');
    }
}
