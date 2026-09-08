<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_create_voyageur_profile_and_toggle_mode()
    {
        $register = $this->postJson('/api/auth/register', [
            'nom' => 'Fall',
            'prenom' => 'Cheikh',
            'telephone' => '+221776543210',
            'email' => 'cheikh@example.com',
            'mot_de_passe' => 'secret123',
        ]);

        $token = $register->json('access_token');
        $headers = ['Authorization' => "Bearer {$token}"];

        // 1. Créer le profil Voyageur
        $voyageurRes = $this->withHeaders($headers)
            ->postJson('/api/profile/voyageur', [
                'type_piece' => 'cni',
                'numero_piece' => '1234567890123',
                'cni_recto' => 'cni_recto.jpg',
                'cni_verso' => 'cni_verso.jpg',
                'mode_client' => false,
            ]);

        $voyageurRes->assertStatus(201)
            ->assertJsonPath('voyageur.type_piece', 'cni')
            ->assertJsonPath('voyageur.mode_client', false);

        $this->assertDatabaseHas('voyageurs', ['type_piece' => 'cni']);

        // 2. Basculer en mode client
        $toggleRes = $this->withHeaders($headers)
            ->postJson('/api/profile/toggle-mode');

        $toggleRes->assertStatus(200)
            ->assertJsonPath('mode_client', true)
            ->assertJsonPath('mode_actuel', 'client');

        // 3. Re-basculer en mode voyageur
        $toggleRes2 = $this->withHeaders($headers)
            ->postJson('/api/profile/toggle-mode');

        $toggleRes2->assertStatus(200)
            ->assertJsonPath('mode_client', false)
            ->assertJsonPath('mode_actuel', 'voyageur');
    }
}
