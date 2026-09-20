<?php

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
        Schema::create('academic_export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('export_type');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->restrictOnDelete();
            $table->string('format');
            $table->json('filter_payload')->nullable();
            $table->unsignedInteger('records_count')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_export_logs');
    }
};
