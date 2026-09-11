<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voyageur;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_list_users()
    {
        $adminUser = User::create([
            'nom' => 'Admin',
            'prenom' => 'Super',
            'telephone' => '+221770000000',
            'email' => 'admin@example.com',
            'mot_de_passe' => 'password123',
            'statut' => 'actif',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminUser->assignRole($adminRole);

        $token = auth('api')->login($adminUser);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => ['id', 'nom', 'prenom', 'email', 'roles'],
                    ],
                ],
            ]);
    }

    public function test_non_admin_cannot_list_users()
    {
        // User simple sans le rôle admin
        $register = $this->postJson('/api/auth/register', [
            'nom' => 'User',
            'prenom' => 'Lambda',
            'telephone' => '+221771111111',
            'email' => 'user@example.com',
            'mot_de_passe' => 'password123',
        ]);

        $token = $register->json('access_token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_update_voyageur_statut()
    {
        $adminUser = User::create([
            'nom' => 'Admin',
            'prenom' => 'Super',
            'telephone' => '+221770000000',
            'email' => 'admin@example.com',
            'mot_de_passe' => 'password123',
            'statut' => 'actif',
        ]);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminUser->assignRole($adminRole);

        $voyageurUser = User::factory()->create();
        $voyageur = Voyageur::factory()->create([
            'user_id' => $voyageurUser->id,
            'statut' => 'en_attente',
        ]);

        $token = auth('api')->login($adminUser);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/voyageurs/{$voyageur->id}/statut", [
                'statut' => 'verifie',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.statut', 'verifie');

        $this->assertDatabaseHas('voyageurs', [
            'id' => $voyageur->id,
            'statut' => 'verifie',
        ]);
    }
}
