<?php

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AssessmentDefinition;
use App\Models\AssessmentComponent;
use App\Models\TeachingAssignment;
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
        Schema::create('assessment_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AcademicYear::class)->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedTinyInteger('weight');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['academic_year_id', 'name'],
                'assessment_definitions_year_name_unique'
            );
        });

        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TeachingAssignment::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(AssessmentDefinition::class)->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('weight');
            $table->timestamps();

            $table->unique(
                ['teaching_assignment_id', 'assessment_definition_id'],
                'assessment_components_assignment_def_unique'
            );
        });

        Schema::create('student_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AssessmentComponent::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(AcademicEnrollment::class)->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->text('notes')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['assessment_component_id', 'academic_enrollment_id'],
                'student_scores_comp_enrollment_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_assessment_scores');
        Schema::dropIfExists('assessment_components');
        Schema::dropIfExists('assessment_definitions');
    }
};
