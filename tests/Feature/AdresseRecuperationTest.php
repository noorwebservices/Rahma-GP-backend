<?php

namespace Tests\Feature;

use App\Models\Adresse_depot;
use App\Models\User;
use App\Models\Voyage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdresseRecuperationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createVoyageurUser(string $email = 'voyageur_recup@example.com', string $phone = '+221770000051'): User
    {
        $res = $this->postJson('/api/auth/register', [
            'nom' => 'Ndiaye',
            'prenom' => 'Fatou',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/voyageur', [
            'type_piece' => 'cni',
            'numero_piece' => '9876543210',
            'mode_client' => false,
        ]);

        return $user->fresh();
    }

    private function createClientUser(string $email = 'client_recup@example.com', string $phone = '+221770000052'): User
    {
        $res = $this->postJson('/api/auth/register', [
            'nom' => 'Ba',
            'prenom' => 'Oumar',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/client');

        return $user->fresh();
    }

    public function test_voyageur_can_create_adresse_recuperation()
    {
        $user = $this->createVoyageurUser();

        $response = $this->actingAs($user, 'api')->postJson('/api/adresse-recuperations', [
            'adresse' => '15 Boulevard Haussmann',
            'ville' => 'Paris',
            'pays' => 'France',
            'horaire_ouverture' => '09h00 - 19h00',
            'instructions' => 'Point Relais Express',
            'latitude' => 48.873,
            'longitude' => 2.332,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Adresse de récupération créée avec succès.')
            ->assertJsonPath('data.adresse', '15 Boulevard Haussmann')
            ->assertJsonPath('data.voyageur_id', $user->voyageur->id);

        $this->assertDatabaseHas('adresse_recuperations', [
            'adresse' => '15 Boulevard Haussmann',
            'voyageur_id' => $user->voyageur->id,
        ]);
    }

    public function test_client_cannot_create_adresse_recuperation()
    {
        $user = $this->createClientUser();

        $response = $this->actingAs($user, 'api')->postJson('/api/adresse-recuperations', [
            'adresse' => '15 Boulevard Haussmann',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);

        $response->assertStatus(403);
    }

    public function test_voyageur_sees_only_their_created_adresses()
    {
        $voyageur1 = $this->createVoyageurUser('vr1@test.com', '+221770000060');
        $voyageur2 = $this->createVoyageurUser('vr2@test.com', '+221770000070');

        $this->actingAs($voyageur1, 'api')->postJson('/api/adresse-recuperations', [
            'adresse' => 'Adresse Recup Voyageur 1',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $this->actingAs($voyageur2, 'api')->postJson('/api/adresse-recuperations', [
            'adresse' => 'Adresse Recup Voyageur 2',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);

        $res1 = $this->actingAs($voyageur1, 'api')->getJson('/api/adresse-recuperations');
        $res1->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.adresse', 'Adresse Recup Voyageur 1');

        $res2 = $this->actingAs($voyageur2, 'api')->getJson('/api/adresse-recuperations');
        $res2->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.adresse', 'Adresse Recup Voyageur 2');
    }

    public function test_client_can_see_all_adresses()
    {
        $v1 = $this->createVoyageurUser('vr1@test.com', '+221770000061');
        $client = $this->createClientUser('cr1@test.com', '+221770000080');

        $this->actingAs($v1, 'api')->postJson('/api/adresse-recuperations', [
            'adresse' => 'Adresse Recup Publique 1',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $res = $this->actingAs($client, 'api')->getJson('/api/adresse-recuperations');
        $res->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_voyageur_cannot_update_another_voyageurs_adresse()
    {
        $v1 = $this->createVoyageurUser('vr1@test.com', '+221770000062');
        $v2 = $this->createVoyageurUser('vr2@test.com', '+221770000072');

        $createRes = $this->actingAs($v1, 'api')->postJson('/api/adresse-recuperations', [
            'adresse' => 'Adresse Recup Originale',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $adresseId = $createRes->json('data.id');

        $updateRes = $this->actingAs($v2, 'api')->putJson("/api/adresse-recuperations/{$adresseId}", [
            'adresse' => 'Adresse Recup Modifiée',
        ]);

        $updateRes->assertStatus(403);
    }

    public function test_cannot_delete_adresse_linked_to_voyage()
    {
        $v1 = $this->createVoyageurUser('vr1@test.com', '+221770000063');

        $createRes = $this->actingAs($v1, 'api')->postJson('/api/adresse-recuperations', [
            'adresse' => 'Adresse Recup liée',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);
        $adresseRecupId = $createRes->json('data.id');

        $adresseDepot = Adresse_depot::create([
            'adresse' => 'Depot',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        Voyage::create([
            'voyageur_id' => $v1->voyageur->id,
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecupId,
            'ville_depart' => 'Dakar',
            'pays_depart' => 'Sénégal',
            'ville_destination' => 'Paris',
            'pays_destination' => 'France',
            'date_depart' => now()->addDays(2),
            'date_arrivee' => now()->addDays(3),
            'capacite_totale' => 20,
            'capacite_dispo' => 20,
            'prix_kg' => 10,
        ]);

        $deleteRes = $this->actingAs($v1, 'api')->deleteJson("/api/adresse-recuperations/{$adresseRecupId}");

        $deleteRes->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Impossible de supprimer cette adresse de récupération car elle est actuellement liée à un ou plusieurs voyages.');

        $this->assertDatabaseHas('adresse_recuperations', ['id' => $adresseRecupId]);
    }
}
