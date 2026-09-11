<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\Revenus_voyageur;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Voyageur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevenusVoyageurTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(User $user): void
    {
        $token = auth('api')->login($user);
        $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function creerVoyageurAvecRevenus(int $count = 3, string $statut = 'disponible'): array
    {
        $user = User::factory()->create();
        $voyageur = Voyageur::factory()->create(['user_id' => $user->id]);
        $voyage = Voyage::factory()->create(['voyageur_id' => $voyageur->id]);

        for ($i = 0; $i < $count; $i++) {
            $reservation = Reservation::factory()->create([
                'voyage_id' => $voyage->id,
                'client_id' => Client::factory()->create()->id,
            ]);

            Revenus_voyageur::factory()->create([
                'voyageur_id' => $voyageur->id,
                'reservation_id' => $reservation->id,
                'montant' => 50.00,
                'statut' => $statut,
            ]);
        }

        return compact('user', 'voyageur');
    }

    #[Test]
    public function voyageur_peut_consulter_son_solde_et_ses_revenus(): void
    {
        ['user' => $user] = $this->creerVoyageurAvecRevenus(3, 'disponible');
        $this->loginAs($user);

        $response = $this->getJson('/api/revenus');

        $response->assertOk()
            ->assertJson([
                'solde_disponible' => 150,
                'total_revenus' => 150,
                'solde_retire' => 0,
            ]);
    }

    #[Test]
    public function client_sans_profil_voyageur_ne_peut_pas_acceder_aux_revenus(): void
    {
        $clientUser = User::factory()->create();
        Client::factory()->create(['user_id' => $clientUser->id]);
        $this->loginAs($clientUser);

        $response = $this->getJson('/api/revenus');

        $response->assertForbidden();
    }

    #[Test]
    public function voyageur_peut_faire_une_demande_de_retrait(): void
    {
        ['user' => $user, 'voyageur' => $voyageur] = $this->creerVoyageurAvecRevenus(2, 'disponible');
        $this->loginAs($user);

        $response = $this->postJson('/api/revenus/retrait');

        $response->assertOk()
            ->assertJson(['montant_retire' => 100]);

        // Les revenus doivent maintenant être au statut "retire"
        $this->assertDatabaseMissing('revenus_voyageurs', [
            'voyageur_id' => $voyageur->id,
            'statut' => 'disponible',
        ]);
        $this->assertDatabaseHas('revenus_voyageurs', [
            'voyageur_id' => $voyageur->id,
            'statut' => 'retire',
        ]);

        // Une notification doit être envoyée
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'revenu',
        ]);
    }

    #[Test]
    public function retrait_echoue_si_solde_disponible_est_vide(): void
    {
        ['user' => $user] = $this->creerVoyageurAvecRevenus(2, 'retire');
        $this->loginAs($user);

        $response = $this->postJson('/api/revenus/retrait');

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Vous n\'avez aucun solde disponible à retirer.');
    }

    #[Test]
    public function les_revenus_des_autres_voyageurs_ne_sont_pas_visibles(): void
    {
        // Créer un autre voyageur avec des revenus
        $this->creerVoyageurAvecRevenus(5, 'disponible');

        // Se connecter en tant qu'un voyageur sans revenus
        $user = User::factory()->create();
        Voyageur::factory()->create(['user_id' => $user->id]);
        $this->loginAs($user);

        $response = $this->getJson('/api/revenus');

        $response->assertOk()
            ->assertJson([
                'total_revenus' => 0,
                'solde_disponible' => 0,
            ]);
    }
}
