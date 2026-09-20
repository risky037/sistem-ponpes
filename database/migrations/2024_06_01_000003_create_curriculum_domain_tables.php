<?php

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Mapel;
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
        Schema::create('mapels', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Kelas::class)->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['kelas_id', 'name']);
        });

        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Kelas::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Mapel::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(AcademicYear::class)->constrained()->restrictOnDelete();
            $table->string('status')->default('Aktif');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['kelas_id', 'mapel_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('mapels');
    }
};
