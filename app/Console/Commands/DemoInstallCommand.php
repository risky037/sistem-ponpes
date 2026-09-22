<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class DemoInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:install 
                            {--fresh : Run migrate:fresh before seeding} 
                            {--santri=20 : Number of demo santri to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and populate realistic presentation-ready demo dataset for Sistem Ponpes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  Sistem Ponpes Fatimah Az-Zahra — Demo Installation');
        $this->info('====================================================');

        $isFresh = (bool) $this->option('fresh');
        $santriCount = max(5, (int) $this->option('santri'));

        if ($isFresh) {
            $this->warn('Running migrate:fresh to wipe and re-create database tables...');
            $this->call('migrate:fresh');
            $this->info('Database tables refreshed successfully.');
        }

        $this->info("Seeding realistic demo dataset with {$santriCount} santri...");

        DemoDataSeeder::$defaultSantriCount = $santriCount;
        $seeder = new DemoDataSeeder($santriCount);
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('Demo installation completed successfully!');
        $this->newLine();

        $this->info('----------------------------------------------------');
        $this->info('  Available Demo Accounts (Password: "password")');
        $this->info('----------------------------------------------------');

        $this->table(
            ['Role', 'Email', 'Name', 'Portal / Scope'],
            [
                ['Administrator', 'admin@pesantren.test', 'Administrator Sistem', 'Full Admin Dashboard & Controls'],
                ['Pengurus', 'pengurus@pesantren.test', 'Ust. H. Abdullah Mansur', 'Academic & Operational Overview'],
                ['Keuangan', 'keuangan@pesantren.test', 'Hj. Siti Mariam', 'Savings & Financial Management'],
                ['Guru', 'guru.ahmad@pesantren.test', 'Ust. Ahmad Fauzi, S.Pd.I', 'Guru Portal (Fiqih 1A, Wali Kelas 1A)'],
                ['Guru', 'guru.hasan@pesantren.test', 'Ust. Muhammad Hasan, Lc.', 'Guru Portal (Nahwu 1A, Wali Kelas 1B)'],
                ['Guru', 'guru.nur@pesantren.test', 'Usth. Nur Laili, M.Pd.', 'Guru Portal (Akhlak 1A, Wali Kelas 2A)'],
                ['Santri', 'santri.1@pesantren.test', 'Muhammad Ali Zainal', 'Santri Read-Only Personal Portal'],
                ['Santri', 'santri.2@pesantren.test', 'Fatimah Az-Zahra', 'Santri Read-Only Personal Portal'],
            ]
        );

        $this->info('All passwords are set to: password');
        $this->info('Santri portal URL: /dashboard (automatically dispatched by role)');
        $this->info('Guru portal URL: /dashboard (automatically dispatched by role)');

        return Command::SUCCESS;
    }
}
