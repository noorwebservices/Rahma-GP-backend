<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmail = 'admin@rahma.com';
        $adminPhone = '770000000';

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'nom' => 'ADMIN',
                'prenom' => 'Super',
                'telephone' => $adminPhone,
                'email' => $adminEmail,
                'mot_de_passe' => 'password123',
                'adresse' => 'Dakar, Sénégal',
                'statut' => 'actif',
            ]
        );

        $roleAdminApi = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $roleAdminWeb = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        if (! $admin->hasRole('admin', 'api')) {
            $admin->assignRole($roleAdminApi);
        }
        if (! $admin->hasRole('admin', 'web')) {
            $admin->assignRole($roleAdminWeb);
        }
    }
}
