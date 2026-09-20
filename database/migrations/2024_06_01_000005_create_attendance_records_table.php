<?php

use App\Models\AcademicEnrollment;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TeachingSession::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(AcademicEnrollment::class)->constrained()->restrictOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamp('marked_at')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['teaching_session_id', 'academic_enrollment_id'],
                'attendance_session_enrollment_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
