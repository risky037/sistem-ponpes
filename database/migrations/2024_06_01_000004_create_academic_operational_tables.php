<?php

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Kelas;
use App\Models\TeachingAssignment;
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
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Kelas::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(TeachingAssignment::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(AcademicYear::class)->constrained()->restrictOnDelete();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['kelas_id', 'teaching_assignment_id', 'academic_year_id', 'day_of_week', 'start_time'],
                'class_schedules_slot_unique'
            );
        });

        Schema::create('academic_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AcademicYear::class)->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('event_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('teaching_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TeachingAssignment::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(ClassSchedule::class)->nullable()->constrained()->restrictOnDelete();
            $table->date('session_date');
            $table->string('status')->default('Planned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_sessions');
        Schema::dropIfExists('academic_calendar_events');
        Schema::dropIfExists('class_schedules');
    }
};
