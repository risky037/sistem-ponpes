<?php

namespace App\Observers;

use App\Models\KamarSantri;
use App\Models\Santri;
use App\Services\SantriRoomCounterService;

class SantriObserver
{
    public function __construct(
        protected SantriRoomCounterService $roomCounterService
    ) {}

    /**
     * Handle the Santri "creating" event.
     */
    public function creating(Santri $santri): void
    {
        $userName = $santri->user?->name ?? 'Santri';
        $activity = class_basename($santri).' '.$userName;
        $santri->CreateLog("Creating $activity");
    }

    /**
     * Handle the Santri "created" event.
     */
    public function created(Santri $santri): void
    {
        $kamarId = $santri->kamar_id;
        if ($kamarId) {
            $kamarSantri = KamarSantri::firstOrCreate(
                ['santri_id' => $santri->id],
                ['kamar_id' => $kamarId]
            );
            $santri->setRelation('kamar_santri', $kamarSantri);
            $this->roomCounterService->increment($kamarId);
            $santri->resetKamarDirty();
        }
    }

    /**
     * Handle the Santri "updating" event.
     */
    public function updating(Santri $santri): void
    {
        $userName = $santri->user?->name ?? 'Santri';
        $activity = class_basename($santri).' '.$userName;
        $santri->CreateLog("Updating $activity");

        if ($santri->isKamarDirty()) {
            $oldKamarId = $santri->getOriginalKamarId();
            $newKamarId = $santri->kamar_id;

            if ($newKamarId) {
                $kamarSantri = KamarSantri::updateOrCreate(
                    ['santri_id' => $santri->id],
                    ['kamar_id' => $newKamarId]
                );
                $santri->setRelation('kamar_santri', $kamarSantri);
            } else {
                $santri->kamar_santri()?->delete();
                $santri->unsetRelation('kamar_santri');
            }

            $this->roomCounterService->syncTransition($oldKamarId, $newKamarId);
            $santri->resetKamarDirty();
        }
    }

    /**
     * Handle the Santri "deleting" event.
     */
    public function deleting(Santri $santri): void
    {
        $userName = $santri->user?->name ?? 'Santri';
        $activity = class_basename($santri).' '.$userName;
        $santri->CreateLog("Deleting $activity");

        $kamarId = $santri->kamar_id;
        if ($kamarId) {
            $this->roomCounterService->decrement($kamarId);
        }
    }
}
