<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Backward-compatible delegation to modular RolePermissionSeeder.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
    }
}
