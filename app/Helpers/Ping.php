<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Ping
{
    /**
     * Check network/internet connectivity using HTTP client.
     */
    public static function to(string $url = 'https://www.google.com'): bool
    {
        try {
            return Http::timeout(3)->get($url)->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
