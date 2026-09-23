<?php

namespace App\Helpers;

class Whatsapp
{
    public static function make($params)
    {
        if (empty($params)) {
            return '';
        }

        $params = preg_replace('/[^0-9]/', '', (string) $params);

        return substr($params, 0, 1) === '0' ? '62'.substr($params, 1) : (substr($params, 0, 1) === '8' ? '62'.$params : $params);
    }

    /**
     * Generate a manual wa.me chat URL.
     */
    public static function url(?string $phoneNumber, ?string $message = null): string
    {
        if (empty($phoneNumber)) {
            return '#';
        }

        $number = self::make($phoneNumber);
        if (empty($number)) {
            return '#';
        }

        if (! empty($message)) {
            return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
        }

        return 'https://wa.me/'.$number;
    }

    /**
     * Generate pre-filled WhatsApp admin password reset request URL.
     */
    public static function adminResetUrl(?string $identifier = null, ?string $name = null): string
    {
        $adminPhone = config('pesantren.admin_whatsapp', '081234567890');
        $pesantrenName = config('pesantren.nama_pesantren', 'Pondok Pesantren Fatimah Az Zahra');

        $message = "Assalamu'alaikum Admin {$pesantrenName},\nSaya membutuhkan bantuan reset password akun DIGITREN.";
        if (! empty($name)) {
            $message .= "\nNama: {$name}";
        }
        if (! empty($identifier)) {
            $message .= "\nEmail / No. Induk: {$identifier}";
        }
        $message .= "\nMohon bantuan untuk reset kredensial saya. Terima kasih.";

        return self::url($adminPhone, $message);
    }
}
