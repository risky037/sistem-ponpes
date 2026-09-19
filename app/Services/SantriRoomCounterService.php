<?php

namespace App\Services;

use App\Models\Kamar;
use App\Models\KamarSantri;

class SantriRoomCounterService
{
    /**
     * Increment kamar head count.
     */
    public function increment(?int $kamarId): void
    {
        if (! $kamarId) {
            return;
        }

        $kamar = Kamar::find($kamarId);
        $kamar?->increment('jumlah_santri');
    }

    /**
     * Decrement kamar head count safely without falling below zero.
     */
    public function decrement(?int $kamarId): void
    {
        if (! $kamarId) {
            return;
        }

        $kamar = Kamar::find($kamarId);
        if ($kamar && $kamar->jumlah_santri > 0) {
            $kamar->decrement('jumlah_santri');
        }
    }

    /**
     * Handle room transition by decrementing old room and incrementing new room.
     */
    public function syncTransition(?int $oldKamarId, ?int $newKamarId): void
    {
        if ($oldKamarId && $oldKamarId !== $newKamarId) {
            $this->decrement($oldKamarId);
        }

        if ($newKamarId && $newKamarId !== $oldKamarId) {
            $this->increment($newKamarId);
        }
    }

    /**
     * Recalculate room headcount based on active assignments.
     */
    public function recalculate(?int $kamarId): void
    {
        if (! $kamarId) {
            return;
        }

        $count = KamarSantri::where('kamar_id', $kamarId)->count();
        Kamar::where('id', $kamarId)->update(['jumlah_santri' => $count]);
    }
}
