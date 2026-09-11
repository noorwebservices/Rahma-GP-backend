<?php

namespace Tests\Feature;

use App\Models\Adresse_depot;
use App\Models\Adresse_recuperation;
use App\Models\User;
use App\Models\Voyage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createVoyageurUser(string $email = 'v_eval@example.com', string $phone = '+221770009999'): User
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Seck',
            'prenom' => 'Cheikh',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/voyageur', [
            'type_piece' => 'cni',
            'numero_piece' => '4455667788',
            'mode_client' => false,
        ]);

        $user->fresh();
        $user->voyageur->update(['statut' => 'verifie']);

        return $user->fresh();
    }

    private function createClientUser(string $email = 'c_eval@example.com', string $phone = '+221770008888'): User
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Sow',
            'prenom' => 'Khadija',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/client');

        return $user->fresh();
    }

    public function test_client_can_evaluate_voyageur_after_accepted_reservation()
    {
        $voyageur = $this->createVoyageurUser();
        $client = $this->createClientUser();

        $depot = Adresse_depot::create(['voyageur_id' => $voyageur->voyageur->id, 'adresse' => 'A', 'ville' => 'Dakar', 'pays' => 'Sénégal']);
        $recup = Adresse_recuperation::create(['voyageur_id' => $voyageur->voyageur->id, 'adresse' => 'B', 'ville' => 'Paris', 'pays' => 'France']);

        $voyage = Voyage::create([
            'voyageur_id' => $voyageur->voyageur->id,
            'adresse_depot_id' => $depot->id,
            'adresse_recuperation_id' => $recup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(3),
            'date_arrivee' => now()->addDays(4),
            'capacite_totale' => 20.0,
            'capacite_dispo' => 20.0,
            'statut' => 'publie',
        ]);

        // Client fait une réservation
        $res = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'wave',
            'colis' => [
                'type' => 'Sac',
                'poids' => 1.0,
                'destinataire_nom' => 'Nom',
                'destinataire_prenom' => 'Prenom',
                'destinataire_numero' => '+33600000000',
                'destinataire_adresse' => 'Paris',
            ],
        ]);

        $reservationId = $res->json('data.id');

        // Voyageur accepte la réservation
        $this->actingAs($voyageur, 'api')->postJson("/api/reservations/{$reservationId}/accepter");

        // Client poste une évaluation
        $evalRes = $this->actingAs($client, 'api')->postJson("/api/reservations/{$reservationId}/evaluations", [
            'note' => 5,
            'commentaire' => 'Excellent voyageur, très ponctuel et réactif !',
        ]);

        $evalRes->assertStatus(201)
            ->assertJsonPath('data.note', 5)
            ->assertJsonPath('data.commentaire', 'Excellent voyageur, très ponctuel et réactif !');

        // Récupérer les évaluations publiques du voyageur
        $pubEval = $this->getJson("/api/voyageurs/{$voyageur->voyageur->id}/evaluations");
        $pubEval->assertStatus(200)
            ->assertJsonPath('moyenne_notes', 5)
            ->assertJsonPath('total_evaluations', 1);
    }

    public function test_cannot_evaluate_same_reservation_twice()
    {
        $voyageur = $this->createVoyageurUser('v_dup@example.com', '+221770007777');
        $client = $this->createClientUser('c_dup@example.com', '+221770006666');

        $depot = Adresse_depot::create(['voyageur_id' => $voyageur->voyageur->id, 'adresse' => 'A', 'ville' => 'Dakar', 'pays' => 'Sénégal']);
        $recup = Adresse_recuperation::create(['voyageur_id' => $voyageur->voyageur->id, 'adresse' => 'B', 'ville' => 'Paris', 'pays' => 'France']);

        $voyage = Voyage::create([
            'voyageur_id' => $voyageur->voyageur->id,
            'adresse_depot_id' => $depot->id,
            'adresse_recuperation_id' => $recup->id,
            'pays_depart' => 'Sénégal',
            'ville_depart' => 'Dakar',
            'pays_destination' => 'France',
            'ville_destination' => 'Paris',
            'date_depart' => now()->addDays(3),
            'date_arrivee' => now()->addDays(4),
            'capacite_totale' => 20.0,
            'capacite_dispo' => 20.0,
            'statut' => 'publie',
        ]);

        $res = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'wave',
            'colis' => [
                'type' => 'Sac',
                'poids' => 1.0,
                'destinataire_nom' => 'Nom',
                'destinataire_prenom' => 'Prenom',
                'destinataire_numero' => '+33600000000',
                'destinataire_adresse' => 'Paris',
            ],
        ]);

        $reservationId = $res->json('data.id');
        $this->actingAs($voyageur, 'api')->postJson("/api/reservations/{$reservationId}/accepter");

        // Première évaluation : 201
        $this->actingAs($client, 'api')->postJson("/api/reservations/{$reservationId}/evaluations", [
            'note' => 4,
        ]);

        // Deuxième évaluation sur la même réservation : 422
        $dupRes = $this->actingAs($client, 'api')->postJson("/api/reservations/{$reservationId}/evaluations", [
            'note' => 5,
        ]);

        $dupRes->assertStatus(422)
            ->assertJsonPath('message', 'Vous avez déjà soumis une évaluation pour cette réservation.');
    }
}
