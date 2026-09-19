<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@gmail.com',
                'role' => 'Administrator',
            ],
            [
                'name' => 'Operator Tabungan',
                'email' => 'keuangan@gmail.com',
                'role' => 'Keuangan',
            ],
            [
                'name' => 'Pengurus Pondok',
                'email' => 'pengurus@gmail.com',
                'role' => 'Pengurus',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            if (! $user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }
}
