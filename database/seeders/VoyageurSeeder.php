<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use App\Models\Voyageur;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class VoyageurSeeder extends Seeder
{
    public function run(): void
    {
        $roleVoyageur = Role::firstOrCreate(['name' => 'voyageur', 'guard_name' => 'api']);
        $roleClient = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'api']);

        // 1. Voyageur 1 : Cheikh Fall
        $user1 = User::firstOrCreate(
            ['email' => 'cheikh.fall@example.com'],
            [
                'nom' => 'Fall',
                'prenom' => 'Cheikh',
                'telephone' => '+221776543210',
                'mot_de_passe' => 'password123',
                'adresse' => 'Thiès, Sénégal',
                'statut' => 'actif',
            ]
        );
        $user1->assignRole($roleVoyageur);
        $user1->assignRole($roleClient);

        Client::firstOrCreate(['user_id' => $user1->id]);
        Voyageur::firstOrCreate(
            ['user_id' => $user1->id],
            [
                'type_piece' => 'cni',
                'numero_piece' => '1342199800123',
                'cni_recto' => 'uploads/cni_cheikh_recto.jpg',
                'cni_verso' => 'uploads/cni_cheikh_verso.jpg',
                'mode_client' => false,
                'statut' => 'verifie',
            ]
        );

        // 2. Voyageur 2 : Moussa Ndiaye
        $user2 = User::firstOrCreate(
            ['email' => 'moussa.ndiaye@example.com'],
            [
                'nom' => 'Ndiaye',
                'prenom' => 'Moussa',
                'telephone' => '+221701112233',
                'mot_de_passe' => 'password123',
                'adresse' => 'Paris, France',
                'statut' => 'actif',
            ]
        );
        $user2->assignRole($roleVoyageur);
        $user2->assignRole($roleClient);

        Client::firstOrCreate(['user_id' => $user2->id]);
        Voyageur::firstOrCreate(
            ['user_id' => $user2->id],
            [
                'type_piece' => 'passport',
                'numero_piece' => 'A09876543',
                'cni_recto' => 'uploads/passport_moussa.jpg',
                'cni_verso' => null,
                'mode_client' => false,
                'statut' => 'verifie',
            ]
        );
    }
}
