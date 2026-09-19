<?php

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('favicon')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('whatsapp_feature')->default(false)->nullable();
            $table->string('kts_master')->nullable();
            $table->bigInteger('sender')->nullable();
            $table->string('whatsapp_api_key')->nullable();
            $table->boolean('log_activity')->default(false)->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->text('pesan_tarik_tunai')->nullable();
            $table->text('pesan_setor_tunai')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('activity');
            $table->timestamps();
        });

        Schema::create('kelas_santris', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Santri::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Kelas::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('kamar_santris', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Santri::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Kamar::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamar_santris');
        Schema::dropIfExists('kelas_santris');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('settings');
    }
};
