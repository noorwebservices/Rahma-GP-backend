<?php

namespace Tests\Feature;

use App\Models\Adresse_recuperation;
use App\Models\User;
use App\Models\Voyage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdresseDepotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createVoyageurUser(string $email = 'voyageur@example.com', string $phone = '+221770000001'): User
    {
        $res = $this->postJson('/api/auth/register', [
            'nom' => 'Diallo',
            'prenom' => 'Moussa',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/voyageur', [
            'type_piece' => 'cni',
            'numero_piece' => '1234567890',
            'mode_client' => false,
        ]);

        return $user->fresh();
    }

    private function createClientUser(string $email = 'client@example.com', string $phone = '+221770000002'): User
    {
        $res = $this->postJson('/api/auth/register', [
            'nom' => 'Sow',
            'prenom' => 'Awa',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/client');

        return $user->fresh();
    }

    public function test_voyageur_can_create_adresse_depot()
    {
        $user = $this->createVoyageurUser();

        $response = $this->actingAs($user, 'api')->postJson('/api/adresse-depots', [
            'adresse' => '10 Rue de la Paix',
            'ville' => 'Paris',
            'pays' => 'France',
            'horaire_ouverture' => '08h00 - 18h00',
            'instructions' => 'Laisser au gardien',
            'latitude' => 48.869,
            'longitude' => 2.331,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Adresse de dépôt créée avec succès.')
            ->assertJsonPath('data.adresse', '10 Rue de la Paix')
            ->assertJsonPath('data.voyageur_id', $user->voyageur->id);

        $this->assertDatabaseHas('adresse_depots', [
            'adresse' => '10 Rue de la Paix',
            'voyageur_id' => $user->voyageur->id,
        ]);
    }

    public function test_client_cannot_create_adresse_depot()
    {
        $user = $this->createClientUser();

        $response = $this->actingAs($user, 'api')->postJson('/api/adresse-depots', [
            'adresse' => '10 Rue de la Paix',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);

        $response->assertStatus(403);
    }

    public function test_voyageur_sees_only_their_created_adresses()
    {
        $voyageur1 = $this->createVoyageurUser('v1@test.com', '+221770000010');
        $voyageur2 = $this->createVoyageurUser('v2@test.com', '+221770000020');

        $this->actingAs($voyageur1, 'api')->postJson('/api/adresse-depots', [
            'adresse' => 'Adresse Voyageur 1',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $this->actingAs($voyageur2, 'api')->postJson('/api/adresse-depots', [
            'adresse' => 'Adresse Voyageur 2',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);

        $res1 = $this->actingAs($voyageur1, 'api')->getJson('/api/adresse-depots');
        $res1->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.adresse', 'Adresse Voyageur 1');

        $res2 = $this->actingAs($voyageur2, 'api')->getJson('/api/adresse-depots');
        $res2->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.adresse', 'Adresse Voyageur 2');
    }

    public function test_client_can_see_all_adresses()
    {
        $v1 = $this->createVoyageurUser('v1@test.com', '+221770000011');
        $client = $this->createClientUser('c1@test.com', '+221770000030');

        $this->actingAs($v1, 'api')->postJson('/api/adresse-depots', [
            'adresse' => 'Adresse Publique 1',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $res = $this->actingAs($client, 'api')->getJson('/api/adresse-depots');
        $res->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_voyageur_cannot_update_another_voyageurs_adresse()
    {
        $v1 = $this->createVoyageurUser('v1@test.com', '+221770000012');
        $v2 = $this->createVoyageurUser('v2@test.com', '+221770000022');

        $createRes = $this->actingAs($v1, 'api')->postJson('/api/adresse-depots', [
            'adresse' => 'Adresse Originale',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $adresseId = $createRes->json('data.id');

        $updateRes = $this->actingAs($v2, 'api')->putJson("/api/adresse-depots/{$adresseId}", [
            'adresse' => 'Adresse Modifiée',
        ]);

        $updateRes->assertStatus(403);
    }

    public function test_cannot_delete_adresse_linked_to_voyage()
    {
        $v1 = $this->createVoyageurUser('v1@test.com', '+221770000013');

        $createRes = $this->actingAs($v1, 'api')->postJson('/api/adresse-depots', [
            'adresse' => 'Adresse liée',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);
        $adresseDepotId = $createRes->json('data.id');

        $adresseRecup = Adresse_recuperation::create([
            'adresse' => 'Recup',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);

        Voyage::create([
            'voyageur_id' => $v1->voyageur->id,
            'adresse_depot_id' => $adresseDepotId,
            'adresse_recuperation_id' => $adresseRecup->id,
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

        $deleteRes = $this->actingAs($v1, 'api')->deleteJson("/api/adresse-depots/{$adresseDepotId}");

        $deleteRes->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Impossible de supprimer cette adresse de dépôt car elle est actuellement liée à un ou plusieurs voyages.');

        $this->assertDatabaseHas('adresse_depots', ['id' => $adresseDepotId]);
    }
}
