<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        |
        | Urutan level:
        |
        | 1. super_admin  = akses paling tinggi
        | 2. admin        = operasional
        | 3. kasubbag     = kewenangan Kasubbag
        | 4. sekjen       = monitoring
        |
        */

        $roles = [
            'super_admin',
            'admin',
            'kasubbag',
            'sekjen',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $users = [
            [
                'username' => 'superadmin',
                'name' => 'Super Administrator',
                'email' => 'superadmin@sitrack.local',
                'password' => Hash::make('super123'),
                'role' => 'super_admin',
                'is_active' => true,
            ],
            [
                'username' => 'admin',
                'name' => 'Admin Operator Persuratan',
                'email' => 'admin@sitrack.local',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ],
            [
                'username' => 'kasubbag',
                'name' => 'Kepala Subbagian TU',
                'email' => 'kasubbag@sitrack.local',
                'password' => Hash::make('kasubbag123'),
                'role' => 'kasubbag',
                'is_active' => true,
            ],
            [
                'username' => 'sekjen',
                'name' => 'Sekretaris Jenderal',
                'email' => 'sekjen@sitrack.local',
                'password' => Hash::make('sekjen123'),
                'role' => 'sekjen',
                'is_active' => true,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create / Update Users
        |--------------------------------------------------------------------------
        */

        foreach ($users as $userData) {

            $user = User::updateOrCreate(
                [
                    'username' => $userData['username'],
                ],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'role' => $userData['role'],
                    'is_active' => $userData['is_active'],
                ]
            );

            // Assign Spatie Role
            $user->syncRoles([
                $userData['role'],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Output
        |--------------------------------------------------------------------------
        */

        $this->command->info('');
        $this->command->info('==========================================');
        $this->command->info('       SiTrack User Seeder Berhasil');
        $this->command->info('==========================================');

        $this->command->info('');
        $this->command->info('SUPERADMIN');
        $this->command->info('Username : superadmin');
        $this->command->info('Email    : superadmin@sitrack.local');
        $this->command->info('Password : super123');

        $this->command->info('');
        $this->command->info('ADMIN');
        $this->command->info('Username : admin');
        $this->command->info('Email    : admin@sitrack.local');
        $this->command->info('Password : admin123');

        $this->command->info('');
        $this->command->info('KASUBBAG');
        $this->command->info('Username : kasubbag');
        $this->command->info('Email    : kasubbag@sitrack.local');
        $this->command->info('Password : kasubbag123');

        $this->command->info('');
        $this->command->info('SEKJEN');
        $this->command->info('Username : sekjen');
        $this->command->info('Email    : sekjen@sitrack.local');
        $this->command->info('Password : sekjen123');

        $this->command->info('');
        $this->command->info('==========================================');
    }
}