<?php

namespace Tests\Feature;

use App\Models\Adresse_depot;
use App\Models\Adresse_recuperation;
use App\Models\User;
use App\Models\Voyage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createVoyageurUser(string $email = 'voyageur_colis@example.com', string $phone = '+221781110000'): User
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Ba',
            'prenom' => 'Oumar',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/voyageur', [
            'type_piece' => 'cni',
            'numero_piece' => '1122334455',
            'mode_client' => false,
        ]);

        $user->fresh();
        $user->voyageur->update(['statut' => 'verifie']);

        return $user->fresh();
    }

    private function createClientUser(string $email = 'client_colis@example.com', string $phone = '+221782220000'): User
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Fall',
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

    private function createPublishedVoyage(User $voyageurUser): Voyage
    {
        $depot = Adresse_depot::create([
            'voyageur_id' => $voyageurUser->voyageur->id,
            'adresse' => 'Dépot 1',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $recup = Adresse_recuperation::create([
            'voyageur_id' => $voyageurUser->voyageur->id,
            'adresse' => 'Recup 1',
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
            'capacite_totale' => 20.0,
            'capacite_dispo' => 20.0,
            'prix_kg' => 10.00,
            'devise' => 'EUR',
            'statut' => 'publie',
        ]);
    }

    public function test_voyageur_can_update_colis_statut_and_create_suivi_entry()
    {
        $voyageur = $this->createVoyageurUser();
        $client = $this->createClientUser();
        $voyage = $this->createPublishedVoyage($voyageur);

        // 1. Créer une réservation par le client
        $resResponse = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'wave',
            'colis' => [
                'type' => 'High-Tech',
                'poids' => 3.0,
                'destinataire_nom' => 'Kane',
                'destinataire_prenom' => 'Moussa',
                'destinataire_numero' => '+33699887766',
                'destinataire_adresse' => '15 Rue de Lyon, Paris',
            ],
        ]);

        $colisId = $resResponse->json('data.colis.id');
        $numeroSuivi = $resResponse->json('data.colis.numero_suivi');

        // 2. Le voyageur met à jour le statut du colis en 'colis_depose'
        $statutResponse = $this->actingAs($voyageur, 'api')->patchJson("/api/colis/{$colisId}/statut", [
            'statut' => 'colis_depose',
            'commentaire' => 'Colis réceptionné au point de dépôt de Dakar.',
        ]);

        $statutResponse->assertStatus(200)
            ->assertJsonPath('message', 'Statut du colis mis à jour avec succès.')
            ->assertJsonPath('data.statut', 'colis_depose');

        $this->assertDatabaseHas('colis', [
            'id' => $colisId,
            'statut' => 'colis_depose',
        ]);

        $this->assertDatabaseHas('suivi_colis', [
            'colis_id' => $colisId,
            'statut' => 'colis_depose',
            'commentaire' => 'Colis réceptionné au point de dépôt de Dakar.',
        ]);

        // 3. Tester l'endpoint public de suivi sans authentification
        $publicSuivi = $this->getJson("/api/colis/suivi/{$numeroSuivi}");
        $publicSuivi->assertStatus(200)
            ->assertJsonPath('data.numero_suivi', $numeroSuivi)
            ->assertJsonPath('data.statut', 'colis_depose');
    }

    public function test_other_client_cannot_update_colis_statut()
    {
        $voyageur = $this->createVoyageurUser('v_sec@example.com', '+221783330000');
        $client1 = $this->createClientUser('c1_sec@example.com', '+221784440000');
        $client2 = $this->createClientUser('c2_sec@example.com', '+221785550000');
        $voyage = $this->createPublishedVoyage($voyageur);

        $resResponse = $this->actingAs($client1, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'wave',
            'colis' => [
                'type' => 'Habits',
                'poids' => 2.0,
                'destinataire_nom' => 'Diop',
                'destinataire_prenom' => 'Saliou',
                'destinataire_numero' => '+33600112233',
                'destinataire_adresse' => '20 Rue Royale, Paris',
            ],
        ]);

        $colisId = $resResponse->json('data.colis.id');

        // Client 2 essaie de modifier le statut du colis -> refusé 403
        $forbiddenRes = $this->actingAs($client2, 'api')->patchJson("/api/colis/{$colisId}/statut", [
            'statut' => 'en_transit',
        ]);

        $forbiddenRes->assertStatus(403);
    }
}
