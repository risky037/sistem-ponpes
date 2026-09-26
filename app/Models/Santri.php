<?php

namespace App\Models;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Santri extends Model
{
    use HasFactory, LogActivity;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNamaLengkapAttribute(): ?string
    {
        return $this->user?->name;
    }

    /**
     * Resiliently resolve photo URL across local and shared hosting.
     */
    public function fotoUrl(): string
    {
        if (empty($this->foto) || $this->foto === 'santri.png') {
            return asset('img/santri.png');
        }

        if (file_exists(public_path('uploads/santri/'.$this->foto))) {
            return asset('uploads/santri/'.$this->foto);
        }

        if (file_exists(public_path('storage/uploads/santri/'.$this->foto)) ||
            file_exists(storage_path('app/public/uploads/santri/'.$this->foto))) {
            return asset('storage/uploads/santri/'.$this->foto);
        }

        return asset('img/santri.png');
    }

    /**
     * Static helper for photo URL with safe fallback.
     */
    public static function getFotoUrl(?self $santri = null): string
    {
        return $santri ? $santri->fotoUrl() : asset('img/santri.png');
    }

    public function wali_santri()
    {
        return $this->hasOne(WaliSantri::class);
    }

    public function kelas_santri()
    {
        return $this->hasOne(KelasSantri::class);
    }

    public function kamar_santri()
    {
        return $this->hasOne(KamarSantri::class);
    }

    /**
     * Get the student's savings account.
     */
    public function tabungan(): HasOne
    {
        return $this->hasOne(Tabungan::class);
    }

    /**
     * Get all savings transactions for the student.
     */
    public function transaksi_tabungan(): HasMany
    {
        return $this->hasMany(TransaksiTabungan::class);
    }

    public function setWhatsAppAttribute($value)
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $value);
        if (substr($phoneNumber, 0, 1) === '0') {
            $this->attributes['whatsapp'] = '62'.substr($phoneNumber, 1);
        } else {
            $this->attributes['whatsapp'] = $phoneNumber;
        }
    }

    public function alamat_santri()
    {
        return $this->hasOne(AlamatSantri::class);
    }

    public function student_batch(): BelongsTo
    {
        return $this->belongsTo(StudentBatch::class);
    }

    public function academic_enrollments(): HasMany
    {
        return $this->hasMany(AcademicEnrollment::class);
    }

    public function academicEnrollments(): HasMany
    {
        return $this->hasMany(AcademicEnrollment::class);
    }

    public function kelasSantri(): HasOne
    {
        return $this->hasOne(KelasSantri::class);
    }

    public function waliSantri(): HasOne
    {
        return $this->hasOne(WaliSantri::class);
    }

    public function kamarSantri(): HasOne
    {
        return $this->hasOne(KamarSantri::class);
    }

    public function pengiriman(): HasMany
    {
        return $this->hasMany(Transfer::class, 'pengirim_id');
    }

    public function penerimaan(): HasMany
    {
        return $this->hasMany(Transfer::class, 'penerima_id');
    }

    /**
     * Pending room ID for lifecycle event processing.
     */
    protected ?int $pendingKamarId = null;

    /**
     * Original room ID prior to pending mutation.
     */
    protected ?int $originalKamarId = null;

    /**
     * Get the room assigned to the student.
     */
    public function kamar(): HasOneThrough
    {
        return $this->hasOneThrough(
            Kamar::class,
            KamarSantri::class,
            'santri_id',
            'id',
            'id',
            'kamar_id'
        );
    }

    /**
     * Get the class assigned to the student via KelasSantri.
     */
    public function kelas(): HasOneThrough
    {
        return $this->hasOneThrough(
            Kelas::class,
            KelasSantri::class,
            'santri_id',
            'id',
            'id',
            'kelas_id'
        );
    }

    /**
     * Get the active academic enrollment for a specific or current academic year.
     */
    public function activeAcademicEnrollment(?int $academicYearId = null): ?AcademicEnrollment
    {
        $query = $this->academic_enrollments()->where('status', AcademicEnrollment::STATUS_AKTIF);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->latest('id')->first();
    }

    /**
     * Get the student's assigned room ID.
     */
    public function getKamarIdAttribute(): ?int
    {
        if ($this->pendingKamarId !== null) {
            return $this->pendingKamarId;
        }

        if (array_key_exists('kamar_id', $this->attributes) && $this->attributes['kamar_id'] !== null) {
            return (int) $this->attributes['kamar_id'];
        }

        return $this->kamar_santri?->kamar_id;
    }

    /**
     * Set the student's assigned room ID.
     */
    public function setKamarIdAttribute($value): void
    {
        $currentId = $this->getKamarIdAttribute();
        if ($this->originalKamarId === null && $currentId !== null) {
            $this->originalKamarId = $currentId;
        }

        $this->pendingKamarId = $value !== null ? (int) $value : null;

        if ($this->exists) {
            $this->updated_at = now()->addSecond();
        }
    }

    /**
     * Get original kamar ID before pending change.
     */
    public function getOriginalKamarId(): ?int
    {
        return $this->originalKamarId ?? $this->kamar_santri?->kamar_id;
    }

    /**
     * Check if room assignment is dirty.
     */
    public function isKamarDirty(): bool
    {
        if ($this->pendingKamarId !== null) {
            return $this->pendingKamarId !== $this->getOriginalKamarId();
        }

        return false;
    }

    /**
     * Reset pending room state after persistence.
     */
    public function resetKamarDirty(): void
    {
        $this->originalKamarId = $this->kamar_santri?->kamar_id ?? $this->pendingKamarId;
        $this->pendingKamarId = null;
    }

    /**
     * Domain Helper: Assign or move student to a room.
     */
    public function assignKamar(int|Kamar $kamar): void
    {
        $kamarId = $kamar instanceof Kamar ? $kamar->id : $kamar;
        $this->kamar_id = $kamarId;
        if ($this->exists) {
            $this->save();
        }
    }
}
