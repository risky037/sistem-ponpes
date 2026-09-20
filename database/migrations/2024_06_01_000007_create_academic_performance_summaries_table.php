<?php

use App\Models\AcademicEnrollment;
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
        Schema::create('academic_performance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AcademicEnrollment::class)->unique()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('total_sessions')->default(0);
            $table->unsignedSmallInteger('present_count')->default(0);
            $table->unsignedSmallInteger('excused_count')->default(0);
            $table->unsignedSmallInteger('sick_count')->default(0);
            $table->unsignedSmallInteger('absent_count')->default(0);
            $table->decimal('attendance_rate', 5, 2)->nullable();
            $table->unsignedSmallInteger('scored_components')->default(0);
            $table->unsignedSmallInteger('total_components')->default(0);
            $table->decimal('weighted_score_sum', 8, 2)->nullable();
            $table->unsignedSmallInteger('total_weight')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->string('computation_status')->default('Kosong');
            $table->string('source_version')->default('v1.0');
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_performance_summaries');
    }
};
