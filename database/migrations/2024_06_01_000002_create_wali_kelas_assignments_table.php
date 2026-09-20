<?php

use App\Models\AcademicYear;
use App\Models\Kelas;
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
        Schema::create('wali_kelas_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Kelas::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(AcademicYear::class)->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['kelas_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wali_kelas_assignments');
    }
};
