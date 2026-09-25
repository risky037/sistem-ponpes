<?php

namespace App\Models;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, LogActivity;

    protected $guarded = ['id'];

    /**
     * Get resilient logo URL with fallback for missing files or storage symlink issues.
     */
    public static function getLogoUrl(?self $setting = null): string
    {
        $instance = $setting ?? static::first();

        return static::resolveAssetUrl($instance?->logo, 'assets/images/logo-icon.png');
    }

    /**
     * Get resilient favicon URL with fallback for missing files or storage symlink issues.
     */
    public static function getFaviconUrl(?self $setting = null): string
    {
        $instance = $setting ?? static::first();

        return static::resolveAssetUrl($instance?->favicon, 'assets/images/favicon-32x32.png');
    }

    /**
     * Instance helper for logo URL.
     */
    public function logoUrl(): string
    {
        return static::getLogoUrl($this);
    }

    /**
     * Instance helper for favicon URL.
     */
    public function faviconUrl(): string
    {
        return static::getFaviconUrl($this);
    }

    /**
     * Resolve public or storage asset URL safely across environments.
     */
    public static function resolveAssetUrl(?string $filename, string $fallbackPath): string
    {
        if (empty($filename)) {
            return asset($fallbackPath);
        }

        $subpath = 'uploads/setting/'.$filename;
        $publicDirectPath = public_path($subpath);
        $publicStoragePath = public_path('storage/'.$subpath);
        $appStoragePath = storage_path('app/public/'.$subpath);

        // 1. Direct public folder (common in shared hosting)
        if (file_exists($publicDirectPath)) {
            return asset($subpath);
        }

        // 2. Standard symlinked storage folder or storage/app/public file
        if (file_exists($publicStoragePath) || file_exists($appStoragePath)) {
            return asset('storage/'.$subpath);
        }

        // 3. Fallback when file is missing from disk
        return asset($fallbackPath);
    }
}
