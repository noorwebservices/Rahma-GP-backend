<?php

namespace Tests\Feature;

use App\Models\Adresse_depot;
use App\Models\Adresse_recuperation;
use App\Models\User;
use App\Models\Voyage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createVoyageurUser(string $email = 'v_msg@example.com', string $phone = '+221770001234'): User
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Ndiaye',
            'prenom' => 'Babacar',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/voyageur', [
            'type_piece' => 'cni',
            'numero_piece' => '123123123',
            'mode_client' => false,
        ]);

        $user->fresh();
        $user->voyageur->update(['statut' => 'verifie']);

        return $user->fresh();
    }

    private function createClientUser(string $email = 'c_msg@example.com', string $phone = '+221770005678'): User
    {
        $this->postJson('/api/auth/register', [
            'nom' => 'Diop',
            'prenom' => 'Mariama',
            'telephone' => $phone,
            'email' => $email,
            'mot_de_passe' => 'password123',
        ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api')->postJson('/api/profile/client');

        return $user->fresh();
    }

    public function test_client_and_voyageur_can_exchange_messages()
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

        // Client crée une réservation
        $res = $this->actingAs($client, 'api')->postJson('/api/reservations', [
            'voyage_id' => $voyage->id,
            'mode_paiement_souhaite' => 'wave',
            'colis' => [
                'type' => 'Habits',
                'poids' => 2.0,
                'destinataire_nom' => 'Test',
                'destinataire_prenom' => 'User',
                'destinataire_numero' => '+33600000000',
                'destinataire_adresse' => 'Paris',
            ],
        ]);

        $reservationId = $res->json('data.id');

        // 1. Client envoie un message au voyageur
        $msgRes1 = $this->actingAs($client, 'api')->postJson("/api/reservations/{$reservationId}/messages", [
            'contenu' => 'Bonjour, quand est-ce que je peux déposer le colis ?',
        ]);

        $msgRes1->assertStatus(201)
            ->assertJsonPath('data.contenu', 'Bonjour, quand est-ce que je peux déposer le colis ?')
            ->assertJsonPath('data.expediteur_id', $client->id)
            ->assertJsonPath('data.destinataire_id', $voyageur->id);

        // Voyageur vérifie les messages non lus
        $unreadRes = $this->actingAs($voyageur, 'api')->getJson('/api/messages/non-lus-count');
        $unreadRes->assertStatus(200)
            ->assertJsonPath('unread_count', 1);

        // 2. Voyageur consulte la discussion (marque les messages comme lus)
        $listRes = $this->actingAs($voyageur, 'api')->getJson("/api/reservations/{$reservationId}/messages");
        $listRes->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Vérifier que le compteur de messages non lus est repassé à 0
        $unreadRes2 = $this->actingAs($voyageur, 'api')->getJson('/api/messages/non-lus-count');
        $unreadRes2->assertStatus(200)
            ->assertJsonPath('unread_count', 0);

        // 3. Voyageur répond
        $msgRes2 = $this->actingAs($voyageur, 'api')->postJson("/api/reservations/{$reservationId}/messages", [
            'contenu' => 'Bonjour, vous pouvez le déposer demain matin à l\'adresse de dépôt.',
        ]);

        $msgRes2->assertStatus(201)
            ->assertJsonPath('data.expediteur_id', $voyageur->id)
            ->assertJsonPath('data.destinataire_id', $client->id);
    }
}
