<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Voyageur;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaiementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function loginAs(User $user): void
    {
        $token = auth('api')->login($user);
        $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function creerAdmin(): User
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        return $user;
    }

    private function creerContexteReservation(): array
    {
        $voyageurUser = User::factory()->create();
        $voyageurRole = Role::findByName('voyageur', 'api');
        $voyageurUser->assignRole($voyageurRole);
        $voyageur = Voyageur::factory()->create(['user_id' => $voyageurUser->id]);
        $voyage = Voyage::factory()->create(['voyageur_id' => $voyageur->id]);

        $clientUser = User::factory()->create();
        $clientRole = Role::findByName('client', 'api');
        $clientUser->assignRole($clientRole);
        $client = Client::factory()->create(['user_id' => $clientUser->id]);
        $reservation = Reservation::factory()->acceptee()->create([
            'voyage_id' => $voyage->id,
            'client_id' => $client->id,
            'montant_total' => 100.00,
        ]);

        return compact('voyageurUser', 'voyageur', 'clientUser', 'client', 'voyage', 'reservation');
    }

    #[Test]
    public function admin_peut_enregistrer_un_paiement_et_credite_revenu_voyageur(): void
    {
        $adminUser = $this->creerAdmin();
        $this->loginAs($adminUser);

        ['reservation' => $reservation, 'voyageur' => $voyageur] = $this->creerContexteReservation();

        $response = $this->postJson("/api/reservations/{$reservation->id}/paiements", [
            'montant' => 100.00,
            'mode_paiement' => 'livraison',
            'statut' => 'reussi',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.statut', 'reussi')
            ->assertJson(['data' => ['montant' => 100]]);

        // Le revenu voyageur doit être crédité automatiquement
        $this->assertDatabaseHas('revenus_voyageurs', [
            'voyageur_id' => $voyageur->id,
            'reservation_id' => $reservation->id,
            'statut' => 'disponible',
        ]);

        // Les deux utilisateurs doivent recevoir une notification
        $this->assertDatabaseCount('notifications', 2);
    }

    #[Test]
    public function impossible_d_enregistrer_deux_paiements_reussis_pour_une_meme_reservation(): void
    {
        $adminUser = $this->creerAdmin();
        $this->loginAs($adminUser);

        ['reservation' => $reservation] = $this->creerContexteReservation();

        Paiement::factory()->create([
            'reservation_id' => $reservation->id,
            'statut' => 'reussi',
            'confirme_par' => $adminUser->id,
        ]);

        $response = $this->postJson("/api/reservations/{$reservation->id}/paiements", [
            'montant' => 100.00,
            'mode_paiement' => 'livraison',
            'statut' => 'reussi',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Un paiement réussi existe déjà pour cette réservation.');
    }

    #[Test]
    public function voyageur_peut_voir_ses_paiements(): void
    {
        ['voyageurUser' => $voyageurUser, 'reservation' => $reservation] = $this->creerContexteReservation();
        $this->loginAs($voyageurUser);

        Paiement::factory()->create([
            'reservation_id' => $reservation->id,
            'confirme_par' => $voyageurUser->id,
        ]);

        $response = $this->getJson('/api/paiements');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function client_peut_voir_ses_paiements(): void
    {
        ['clientUser' => $clientUser, 'reservation' => $reservation] = $this->creerContexteReservation();
        $this->loginAs($clientUser);

        Paiement::factory()->create([
            'reservation_id' => $reservation->id,
            'confirme_par' => $clientUser->id,
        ]);

        $response = $this->getJson('/api/paiements');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function voyageur_ne_voit_pas_les_paiements_d_un_autre_voyageur(): void
    {
        ['voyageurUser' => $voyageurUser] = $this->creerContexteReservation();
        $this->loginAs($voyageurUser);

        // Paiement sur une réservation d'un autre voyage
        $autreReservation = Reservation::factory()->create();
        Paiement::factory()->create(['reservation_id' => $autreReservation->id]);

        $response = $this->getJson('/api/paiements');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
