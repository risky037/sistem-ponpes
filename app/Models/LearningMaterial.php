<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    public const CONTENT_TYPE_TEXT = 'text';

    public const CONTENT_TYPE_PDF = 'pdf';

    public const CONTENT_TYPE_VIDEO = 'video';

    public const CONTENT_TYPE_LINK = 'link';

    public const CONTENT_TYPE_MIXED = 'mixed';

    public const ALLOWED_CONTENT_TYPES = [
        self::CONTENT_TYPE_TEXT,
        self::CONTENT_TYPE_PDF,
        self::CONTENT_TYPE_VIDEO,
        self::CONTENT_TYPE_LINK,
        self::CONTENT_TYPE_MIXED,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $table = 'learning_materials';

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include published materials.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope a query to only include draft materials.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope a query to only include archived materials.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope a query to only include materials visible to a specific class.
     */
    public function scopeForClass(Builder $query, int|Kelas $kelas): Builder
    {
        $kelasId = $kelas instanceof Kelas ? $kelas->id : $kelas;

        return $query->where(function (Builder $sub) use ($kelasId) {
            $sub->whereHas('targets', function (Builder $q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            })->orWhere('kelas_id', $kelasId);
        });
    }

    /**
     * Teacher (guru) who owns/teaches the material.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * User who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Subject (Mata Pelajaran).
     */
    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    /**
     * Primary class reference (if any).
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /**
     * Academic Year.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Academic Year snake_case alias.
     */
    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Physical file attachments.
     */
    public function files(): HasMany
    {
        return $this->hasMany(LearningMaterialFile::class, 'learning_material_id');
    }

    public function learningMaterialFiles(): HasMany
    {
        return $this->files();
    }

    public function learning_material_files(): HasMany
    {
        return $this->files();
    }

    /**
     * Targeted classes receiving this material.
     */
    public function targets(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'learning_material_targets', 'learning_material_id', 'kelas_id')
            ->withTimestamps();
    }

    public function targetClasses(): BelongsToMany
    {
        return $this->targets();
    }

    public function target_classes(): BelongsToMany
    {
        return $this->targets();
    }

    /**
     * Helper to check if material is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Helper to check if material is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Helper to check if material is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Helper to check if material has any attachments.
     */
    public function hasFiles(): bool
    {
        return $this->relationLoaded('files')
            ? $this->files->isNotEmpty()
            : $this->files()->exists();
    }
}
