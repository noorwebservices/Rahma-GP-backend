<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $roleClient = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'api']);

        // 1. Administrateur : Hapsatou Thiam
        $admin = User::firstOrCreate(
            ['email' => 'hapsatou.thiam@example.com'],
            [
                'nom' => 'Thiam',
                'prenom' => 'Hapsatou',
                'telephone' => '+221770000000',
                'mot_de_passe' => 'password123',
                'adresse' => 'Dakar Plateau, Sénégal',
                'statut' => 'actif',
            ]
        );
        $admin->assignRole($roleAdmin);

        // 2. Client 1 : Amadou Sow
        $user1 = User::firstOrCreate(
            ['email' => 'amadou.sow@example.com'],
            [
                'nom' => 'Sow',
                'prenom' => 'Amadou',
                'telephone' => '+221771234567',
                'mot_de_passe' => 'password123',
                'adresse' => 'Fann Residence, Dakar',
                'statut' => 'actif',
            ]
        );
        $user1->assignRole($roleClient);
        Client::firstOrCreate(['user_id' => $user1->id]);

        // 3. Client 2 : Fatou Diallo
        $user2 = User::firstOrCreate(
            ['email' => 'fatou.diallo@example.com'],
            [
                'nom' => 'Diallo',
                'prenom' => 'Fatou',
                'telephone' => '+221779876543',
                'mot_de_passe' => 'password123',
                'adresse' => 'Sacré-Cœur, Dakar',
                'statut' => 'actif',
            ]
        );
        $user2->assignRole($roleClient);
        Client::firstOrCreate(['user_id' => $user2->id]);
    }
}
