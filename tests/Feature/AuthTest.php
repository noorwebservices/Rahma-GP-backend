<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Sow',
            'prenom' => 'Amadou',
            'telephone' => '+221771234567',
            'email' => 'amadou@example.com',
            'mot_de_passe' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'access_token',
                'token_type',
                'user' => ['id', 'nom', 'prenom', 'email', 'roles', 'client'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'amadou@example.com']);
        $this->assertDatabaseHas('clients', []);
    }

    public function test_user_can_login_with_email()
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Diallo',
            'prenom' => 'Fatou',
            'telephone' => '+221779876543',
            'email' => 'fatou@example.com',
            'mot_de_passe' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'fatou@example.com',
            'mot_de_passe' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user']);
    }

    public function test_user_can_login_with_telephone()
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Ndiaye',
            'prenom' => 'Moussa',
            'telephone' => '+221701112233',
            'email' => 'moussa@example.com',
            'mot_de_passe' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => '+221701112233',
            'mot_de_passe' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user']);
    }

    public function test_authenticated_user_can_fetch_me()
    {
        $register = $this->postJson('/api/auth/register', [
            'nom' => 'Ba',
            'prenom' => 'Oumar',
            'telephone' => '+221783334455',
            'email' => 'oumar@example.com',
            'mot_de_passe' => 'secret123',
        ]);

        $token = $register->json('access_token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'oumar@example.com');
    }

    public function test_user_can_logout()
    {
        $register = $this->postJson('/api/auth/register', [
            'nom' => 'Kane',
            'prenom' => 'Awa',
            'telephone' => '+221764445566',
            'email' => 'awa@example.com',
            'mot_de_passe' => 'secret123',
        ]);

        $token = $register->json('access_token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Déconnexion réussie.']);
    }
}
