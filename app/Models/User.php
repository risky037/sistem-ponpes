<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, LogActivity, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function santri()
    {
        return $this->hasOne(Santri::class);
    }

    public function wali_kelas_assignments()
    {
        return $this->hasMany(WaliKelasAssignment::class);
    }

    public function teaching_assignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function gradedScores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class, 'graded_by');
    }

    public function graded_scores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class, 'graded_by');
    }

    public function capturedKpiSnapshots(): HasMany
    {
        return $this->hasMany(AcademicKpiSnapshot::class, 'captured_by');
    }

    public function captured_kpi_snapshots(): HasMany
    {
        return $this->hasMany(AcademicKpiSnapshot::class, 'captured_by');
    }
}
