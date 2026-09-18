<?php

namespace Tests\Feature;

use App\Models\Adresse_depot;
use App\Models\Adresse_recuperation;
use App\Models\User;
use App\Models\Voyage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createVoyageurUser(string $email = 'voyageur_res@example.com', string $phone = '+221771110000'): User
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Ndiaye',
            'prenom' => 'Modou',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/voyageur', [
            'type_piece' => 'cni',
            'numero_piece' => '9988776655',
            'mode_client' => false,
        ]);

        $user->fresh();
        $user->voyageur->update(['statut' => 'verifie']);

        return $user->fresh();
    }

    private function createClientUser(string $email = 'client_res@example.com', string $phone = '+221772220000'): User
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Sarr',
            'prenom' => 'Fatou',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/client');

        return $user->fresh();
    }

    private function createPublishedVoyage(User $voyageurUser, float $capacite = 20.0): Voyage
    {
        $depot = Adresse_depot::create([
            'voyageur_id' => $voyageurUser->voyageur->id,
            'adresse' => 'Aéroport DSS',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $recup = Adresse_recuperation::create([
            'voyageur_id' => $voyageurUser->voyageur->id,
            'adresse' => 'Aéroport CDG',
            'ville' => 'Paris',
            'pays' => 'France',
        ]);

        return Voyage::create([
            'voyageur_id' => $voyageurUser->voyageur->id,
            'adresse_depot_id' => $depot->id,
            'adresse_recuperation_id' => $recup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(3),
            'date_arrivee' => now()->addDays(4),
            'capacite_totale' => $capacite,
            'capacite_dispo' => $capacite,
            'prix_kg' => 10.00,
            'prix_objet' => 5.00,
            'devise' => 'EUR',
            'statut' => 'publie',
        ]);
    }

    public function test_client_can_create_reservation_with_colis()
    {
        $voyageur = $this->createVoyageurUser();
        $client = $this->createClientUser();
        $voyage = $this->createPublishedVoyage($voyageur, 25.0);

        $response = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'wave',
            'colis' => [
                'type' => 'Électronique',
                'description' => 'Ordinateur portable en carton',
                'valeur_estimee' => 500.00,
                'poids' => 4.5,
                'est_fragile' => true,
                'destinataire_nom' => 'Faye',
                'destinataire_prenom' => 'Ibrahima',
                'destinataire_numero' => '+33612345678',
                'destinataire_adresse' => '10 Rue de Paris, 75001 Paris',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Réservation effectuée avec succès.')
            ->assertJsonPath('data.statut', 'en_attente')
            ->assertJsonPath('data.montant_total', 5) // Forfait prix_objet = 5
            ->assertJsonPath('data.colis.type', 'Électronique')
            ->assertJsonPath('data.colis.poids', 4.5);

        $this->assertDatabaseHas('reservations', [
            'voyage_id' => $voyage->id,
            'client_id' => $client->client->id,
            'statut' => 'en_attente',
        ]);

        $this->assertDatabaseHas('colis', [
            'type' => 'Électronique',
            'destinataire_nom' => 'Faye',
        ]);
    }

    public function test_cannot_create_reservation_if_poids_exceeds_available_capacity()
    {
        $voyageur = $this->createVoyageurUser('v_cap@example.com', '+221773330000');
        $client = $this->createClientUser('c_cap@example.com', '+221774440000');
        $voyage = $this->createPublishedVoyage($voyageur, 5.0); // Seulement 5kg dispo

        $response = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'wave',
            'colis' => [
                'type' => 'Vêtements',
                'poids' => 10.0, // Trop lourd
                'destinataire_nom' => 'Faye',
                'destinataire_prenom' => 'Ibrahima',
                'destinataire_numero' => '+33612345678',
                'destinataire_adresse' => '10 Rue de Paris, 75001 Paris',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'La capacité disponible du voyage est insuffisante pour ce colis (Disponible : 5.00 kg).');
    }

    public function test_voyageur_can_accept_reservation_and_capacite_dispo_is_updated()
    {
        $voyageur = $this->createVoyageurUser('v_acc@example.com', '+221775550000');
        $client = $this->createClientUser('c_acc@example.com', '+221776660000');
        $voyage = $this->createPublishedVoyage($voyageur, 10.0);

        $resResponse = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'espece_depot',
            'colis' => [
                'type' => 'Livres',
                'poids' => 6.0,
                'destinataire_nom' => 'Diallo',
                'destinataire_prenom' => 'Mamadou',
                'destinataire_numero' => '+33611112222',
                'destinataire_adresse' => '5 Avenue Victor Hugo',
            ],
        ]);

        $reservationId = $resResponse->json('data.id');

        // Voyageur accepte la réservation
        $acceptResponse = $this->actingAs($voyageur, 'api')->postJson("/api/reservations/{$reservationId}/accepter");

        $acceptResponse->assertStatus(200)
            ->assertJsonPath('message', 'Réservation acceptée avec succès.')
            ->assertJsonPath('data.statut', 'acceptee')
            ->assertJsonPath('data.colis.statut', 'reservation_acceptee');

        $voyage->refresh();
        $this->assertEquals(4.0, $voyage->capacite_dispo); // 10 - 6 = 4
        $this->assertEquals('publie', $voyage->statut);
    }

    public function test_voyageur_accepting_last_capacity_changes_voyage_status_to_complet()
    {
        $voyageur = $this->createVoyageurUser('v_full@example.com', '+221777770000');
        $client = $this->createClientUser('c_full@example.com', '+221778880000');
        $voyage = $this->createPublishedVoyage($voyageur, 10.0);

        $resResponse = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'livraison',
            'colis' => [
                'type' => 'Documents',
                'poids' => 10.0, // Utilise toute la capacité
                'destinataire_nom' => 'Diallo',
                'destinataire_prenom' => 'Mamadou',
                'destinataire_numero' => '+33611112222',
                'destinataire_adresse' => '5 Avenue Victor Hugo',
            ],
        ]);

        $reservationId = $resResponse->json('data.id');

        $this->actingAs($voyageur, 'api')->postJson("/api/reservations/{$reservationId}/accepter");

        $voyage->refresh();
        $this->assertEquals(0.0, $voyage->capacite_dispo);
        $this->assertEquals('complet', $voyage->statut);
    }

    public function test_client_canceling_accepted_reservation_restores_capacity()
    {
        $voyageur = $this->createVoyageurUser('v_canc@example.com', '+221779990000');
        $client = $this->createClientUser('c_canc@example.com', '+221770001111');
        $voyage = $this->createPublishedVoyage($voyageur, 10.0);

        $resResponse = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'wave',
            'colis' => [
                'type' => 'Colis test',
                'poids' => 10.0,
                'destinataire_nom' => 'Nom',
                'destinataire_prenom' => 'Prénom',
                'destinataire_numero' => '+33600000000',
                'destinataire_adresse' => 'Adresse test',
            ],
        ]);

        $reservationId = $resResponse->json('data.id');

        // Voyageur accepte -> voyage devient 'complet', capacite_dispo = 0
        $this->actingAs($voyageur, 'api')->postJson("/api/reservations/{$reservationId}/accepter");

        // Client annule -> la capacité est réattribuée, voyage repasse à 'publie'
        $cancelResponse = $this->actingAs($client, 'api')->postJson("/api/reservations/{$reservationId}/annuler");
        $cancelResponse->assertStatus(200)
            ->assertJsonPath('data.statut', 'annulee');

        $voyage->refresh();
        $this->assertEquals(10.0, $voyage->capacite_dispo);
        $this->assertEquals('publie', $voyage->statut);
    }
}
