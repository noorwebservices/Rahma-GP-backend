<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdminApi = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $roleAdminWeb = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@rahma.sn'],
            [
                'nom' => 'Admin',
                'prenom' => 'Rahma',
                'telephone' => '+221770000000',
                'mot_de_passe' => 'Admin@2026',
                'adresse' => 'Dakar, Sénégal',
                'statut' => 'actif',
            ]
        );

        $admin->assignRole([$roleAdminApi, $roleAdminWeb]);
    }
}
