<?php

namespace App\Observers;

use App\Models\Setting;

class SettingObserver
{
    /**
     * Handle the Setting "creating" event.
     */
    public function creating(Setting $setting): void
    {
        $activity = class_basename($setting);
        $setting->CreateLog('Creatting '.$activity);
    }

    /**
     * Handle the Setting "updating" event.
     */
    public function updating(Setting $setting): void
    {
        $activity = class_basename($setting);
        $setting->CreateLog('Updating '.$activity);
    }

    /**
     * Handle the Setting "deleting" event.
     */
    public function deleting(Setting $setting): void
    {
        $activity = class_basename($setting);
        $setting->CreateLog('Deleting '.$activity);
    }
}
