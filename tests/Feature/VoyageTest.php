<?php

namespace Tests\Feature;

use App\Models\Adresse_depot;
use App\Models\Adresse_recuperation;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Voyageur;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoyageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createVoyageurUser(string $email = 'voyageur_v@example.com', string $phone = '+221770000999', string $statut = 'verifie'): User
    {
        $res = $this->postJson('/api/auth/register', [
            'nom' => 'Sow',
            'prenom' => 'Amadou',
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

        $user->fresh();
        /** @var Voyageur $voyageur */
        $voyageur = $user->voyageur;
        $voyageur->update(['statut' => $statut]);

        return $user->fresh();
    }

    private function createClientUser(string $email = 'client_v@example.com', string $phone = '+221770000888'): User
    {
        $res = $this->postJson('/api/auth/register', [
            'nom' => 'Kouyaté',
            'prenom' => 'Aminata',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/client');

        return $user->fresh();
    }

    public function test_verified_voyageur_can_create_voyage()
    {
        $user = $this->createVoyageurUser();

        $adresseDepot = Adresse_depot::create([
            'voyageur_id' => $user->voyageur->id,
            'adresse' => 'Dépot 1',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $adresseRecup = Adresse_recuperation::create([
            'voyageur_id' => $user->voyageur->id,
            'adresse' => 'Recup 1',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/voyages', [
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'date_arrivee' => now()->addDays(6)->format('Y-m-d H:i:s'),
            'capacite_totale' => 30,
            'prix_kg' => 12.50,
            'devise' => 'XOF',
            'description' => 'Voyage Dakar -> Paris',
            'statut' => 'brouillon',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Voyage créé avec succès.')
            ->assertJsonPath('data.capacite_dispo', 30)
            ->assertJsonPath('data.statut', 'brouillon');

        $this->assertDatabaseHas('voyages', [
            'voyageur_id' => $user->voyageur->id,
            'ville_depart' => 'Dakar',
            'ville_destination' => 'Paris',
        ]);
    }

    public function test_unverified_voyageur_cannot_create_voyage()
    {
        $user = $this->createVoyageurUser('unverified@example.com', '+221770000777', 'en_attente');

        $adresseDepot = Adresse_depot::create([
            'voyageur_id' => $user->voyageur->id,
            'adresse' => 'Dépot 1',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $adresseRecup = Adresse_recuperation::create([
            'voyageur_id' => $user->voyageur->id,
            'adresse' => 'Recup 1',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/voyages', [
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'date_arrivee' => now()->addDays(6)->format('Y-m-d H:i:s'),
            'capacite_totale' => 30,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Votre profil voyageur doit être vérifié par un administrateur pour pouvoir créer un voyage.');
    }

    public function test_client_cannot_create_voyage()
    {
        $clientUser = $this->createClientUser();

        $response = $this->actingAs($clientUser, 'api')->postJson('/api/voyages', [
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
        ]);

        $response->assertStatus(403);
    }

    public function test_date_arrivee_must_be_after_date_depart()
    {
        $user = $this->createVoyageurUser('date@example.com', '+221770000666');

        $adresseDepot = Adresse_depot::create(['adresse' => 'D', 'ville' => 'Dakar', 'pays' => 'Sénégal']);
        $adresseRecup = Adresse_recuperation::create(['adresse' => 'R', 'ville' => 'Paris', 'pays' => 'France']);

        $response = $this->actingAs($user, 'api')->postJson('/api/voyages', [
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'date_arrivee' => now()->addDays(2)->format('Y-m-d H:i:s'), // Invalide : avant le départ
            'capacite_totale' => 30,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_arrivee']);
    }

    public function test_voyageur_can_publish_and_cancel_voyage()
    {
        $user = $this->createVoyageurUser('pub@example.com', '+221770000555');

        $adresseDepot = Adresse_depot::create(['adresse' => 'D', 'ville' => 'Dakar', 'pays' => 'Sénégal']);
        $adresseRecup = Adresse_recuperation::create(['adresse' => 'R', 'ville' => 'Paris', 'pays' => 'France']);

        $createRes = $this->actingAs($user, 'api')->postJson('/api/voyages', [
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'date_arrivee' => now()->addDays(6)->format('Y-m-d H:i:s'),
            'capacite_totale' => 30,
            'statut' => 'brouillon',
        ]);

        $voyageId = $createRes->json('data.id');

        // 1. Publier le voyage
        $pubRes = $this->actingAs($user, 'api')->postJson("/api/voyages/{$voyageId}/publier");
        $pubRes->assertStatus(200)
            ->assertJsonPath('data.statut', 'publie');

        // 2. Annuler le voyage
        $annulRes = $this->actingAs($user, 'api')->postJson("/api/voyages/{$voyageId}/annuler");
        $annulRes->assertStatus(200)
            ->assertJsonPath('data.statut', 'annule');
    }

    public function test_client_sees_published_and_reserved_voyages()
    {
        $v1 = $this->createVoyageurUser('v1_voyage@example.com', '+221770000444');
        $clientUser = $this->createClientUser('c1_voyage@example.com', '+221770000333');

        $adresseDepot = Adresse_depot::create(['adresse' => 'D', 'ville' => 'Dakar', 'pays' => 'Sénégal']);
        $adresseRecup = Adresse_recuperation::create(['adresse' => 'R', 'ville' => 'Paris', 'pays' => 'France']);

        // Voyage 1 : Publié
        $vPublie = Voyage::create([
            'voyageur_id' => $v1->voyageur->id,
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(2),
            'date_arrivee' => now()->addDays(3),
            'capacite_totale' => 20,
            'capacite_dispo' => 20,
            'statut' => 'publie',
        ]);

        // Voyage 2 : En cours mais le client a réservé
        $vReserve = Voyage::create([
            'voyageur_id' => $v1->voyageur->id,
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(2),
            'date_arrivee' => now()->addDays(3),
            'capacite_totale' => 20,
            'capacite_dispo' => 15,
            'statut' => 'en_cours',
        ]);

        Reservation::create([
            'numero' => 'RES-10001',
            'client_id' => $clientUser->client->id,
            'voyage_id' => $vReserve->id,
            'montant_total' => 50.00,
            'mode_paiement_souhaite' => 'wave',
            'statut' => 'acceptee',
        ]);

        // Voyage 3 : Brouillon d'un autre voyageur (le client ne doit PAS le voir)
        Voyage::create([
            'voyageur_id' => $v1->voyageur->id,
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(2),
            'date_arrivee' => now()->addDays(3),
            'capacite_totale' => 20,
            'capacite_dispo' => 20,
            'statut' => 'brouillon',
        ]);

        $res = $this->actingAs($clientUser, 'api')->getJson('/api/voyages');
        $res->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Seuls Voyage 1 (publié) et Voyage 2 (réservé par lui)
    }

    public function test_cannot_delete_voyage_with_reservations()
    {
        $v1 = $this->createVoyageurUser('v1_del@example.com', '+221770000222');
        $clientUser = $this->createClientUser('c1_del@example.com', '+221770000111');

        $adresseDepot = Adresse_depot::create(['adresse' => 'D', 'ville' => 'Dakar', 'pays' => 'Sénégal']);
        $adresseRecup = Adresse_recuperation::create(['adresse' => 'R', 'ville' => 'Paris', 'pays' => 'France']);

        $voyage = Voyage::create([
            'voyageur_id' => $v1->voyageur->id,
            'adresse_depot_id' => $adresseDepot->id,
            'adresse_recuperation_id' => $adresseRecup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(2),
            'date_arrivee' => now()->addDays(3),
            'capacite_totale' => 20,
            'capacite_dispo' => 15,
            'statut' => 'publie',
        ]);

        Reservation::create([
            'numero' => 'RES-10002',
            'client_id' => $clientUser->client->id,
            'voyage_id' => $voyage->id,
            'montant_total' => 50.00,
            'mode_paiement_souhaite' => 'wave',
            'statut' => 'acceptee',
        ]);

        $deleteRes = $this->actingAs($v1, 'api')->deleteJson("/api/voyages/{$voyage->id}");

        $deleteRes->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Impossible de supprimer ce voyage car il possède déjà des réservations associées.');

        $this->assertDatabaseHas('voyages', ['id' => $voyage->id]);
    }
}
