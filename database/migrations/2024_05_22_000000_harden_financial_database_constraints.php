<?php

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
        // 1. Pre-flight integrity check: verify zero duplicate tabungan accounts
        $duplicateCount = DB::table('tabungans')
            ->select('santri_id')
            ->groupBy('santri_id')
            ->havingRaw('count(*) > 1')
            ->count();

        if ($duplicateCount > 0) {
            throw new RuntimeException("Pre-migration check failed: {$duplicateCount} duplicate tabungan accounts detected. Clean data before running migration.");
        }

        // 2. tabungans: enforce UNIQUE(santri_id) and ON DELETE RESTRICT
        Schema::table('tabungans', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->unique('santri_id');
            $table->foreign('santri_id')
                ->references('id')
                ->on('santris')
                ->onDelete('restrict');
        });

        // 3. transaksi_tabungans: enforce ON DELETE RESTRICT and add composite index
        Schema::table('transaksi_tabungans', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->foreign('santri_id')
                ->references('id')
                ->on('santris')
                ->onDelete('restrict');
            $table->index(['santri_id', 'tanggal_transaksi', 'jenis_transaksi'], 'idx_transaksi_santri_tgl_jenis');
        });

        // 4. transfers: enforce ON DELETE RESTRICT and add composite indexes
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['pengirim_id']);
            $table->dropForeign(['penerima_id']);
            $table->foreign('pengirim_id')
                ->references('id')
                ->on('santris')
                ->onDelete('restrict');
            $table->foreign('penerima_id')
                ->references('id')
                ->on('santris')
                ->onDelete('restrict');
            $table->index(['pengirim_id', 'created_at'], 'idx_transfers_pengirim_created');
            $table->index(['penerima_id', 'created_at'], 'idx_transfers_penerima_created');
        });

        // 5. Database CHECK constraints (Supported in MariaDB 10.2+ and MySQL 8.0.16+)
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE tabungans ADD CONSTRAINT chk_tabungans_saldo CHECK (saldo >= 0)');
            DB::statement('ALTER TABLE transfers ADD CONSTRAINT chk_transfers_jumlah CHECK (jumlah_transfer > 0)');
            DB::statement('ALTER TABLE transfers ADD CONSTRAINT chk_transfers_parties CHECK (pengirim_id != penerima_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE tabungans DROP CONSTRAINT chk_tabungans_saldo');
            DB::statement('ALTER TABLE transfers DROP CONSTRAINT chk_transfers_jumlah');
            DB::statement('ALTER TABLE transfers DROP CONSTRAINT chk_transfers_parties');
        }

        // Revert transfers
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['pengirim_id']);
            $table->dropForeign(['penerima_id']);
            $table->dropIndex('idx_transfers_pengirim_created');
            $table->dropIndex('idx_transfers_penerima_created');
            $table->foreign('pengirim_id')
                ->references('id')
                ->on('santris')
                ->onDelete('cascade');
            $table->foreign('penerima_id')
                ->references('id')
                ->on('santris')
                ->onDelete('cascade');
        });

        // Revert transaksi_tabungans
        Schema::table('transaksi_tabungans', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->dropIndex('idx_transaksi_santri_tgl_jenis');
            $table->foreign('santri_id')
                ->references('id')
                ->on('santris')
                ->onDelete('cascade');
        });

        // Revert tabungans
        Schema::table('tabungans', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
            $table->dropUnique(['santri_id']);
            $table->foreign('santri_id')
                ->references('id')
                ->on('santris')
                ->onDelete('cascade');
        });
    }
};
