<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * One demo account per role, all sharing the password below. These are
     * DEMO credentials for local development only — see README for the
     * documented list. Never use this password set in production.
     */
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $accounts = [
            ['name' => 'Admin Dune', 'email' => 'admin@dune-erp.test', 'role' => 'admin'],
            ['name' => 'Direction Dune', 'email' => 'direction@dune-erp.test', 'role' => 'direction'],
            ['name' => 'Manager Dune', 'email' => 'manager@dune-erp.test', 'role' => 'manager'],
            ['name' => 'Caissier Dune', 'email' => 'caissier@dune-erp.test', 'role' => 'caissier'],
            ['name' => 'Serveur Dune', 'email' => 'serveur@dune-erp.test', 'role' => 'serveur'],
            ['name' => 'Cuisine Dune', 'email' => 'cuisine@dune-erp.test', 'role' => 'cuisine'],
            ['name' => 'Bar Dune', 'email' => 'bar@dune-erp.test', 'role' => 'bar'],
            ['name' => 'Stock Dune', 'email' => 'stock@dune-erp.test', 'role' => 'stock'],
            ['name' => 'Comptable Dune', 'email' => 'comptable@dune-erp.test', 'role' => 'comptable'],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
