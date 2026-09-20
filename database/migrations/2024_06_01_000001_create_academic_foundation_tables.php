<?php

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\StudentBatch;
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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('semester');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['name', 'semester']);
        });

        Schema::create('student_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('year')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AcademicYear::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Santri::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Kelas::class)->constrained()->restrictOnDelete();
            $table->string('status')->default('Aktif');
            $table->date('enrolled_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'santri_id']);
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->foreignIdFor(StudentBatch::class)->nullable()->after('user_id')->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropForeign(['student_batch_id']);
            $table->dropColumn('student_batch_id');
        });

        Schema::dropIfExists('academic_enrollments');
        Schema::dropIfExists('student_batches');
        Schema::dropIfExists('academic_years');
    }
};
