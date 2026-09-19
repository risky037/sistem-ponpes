<?php

use App\Models\Santri;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Santri::class, 'pengirim_id')->constrained('santris')->restrictOnDelete();
            $table->foreignIdFor(Santri::class, 'penerima_id')->constrained('santris')->restrictOnDelete();
            $table->bigInteger('jumlah_transfer');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index(['pengirim_id', 'created_at'], 'idx_transfers_pengirim_created');
            $table->index(['penerima_id', 'created_at'], 'idx_transfers_penerima_created');
        });

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE transfers ADD CONSTRAINT chk_transfers_jumlah CHECK (jumlah_transfer > 0)');
            DB::statement('ALTER TABLE transfers ADD CONSTRAINT chk_transfers_parties CHECK (pengirim_id != penerima_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
