<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntrepriseGpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gerant_can_register_entreprise(): void
    {
        $payload = [
            'nom_gerant' => 'Sow',
            'prenom_gerant' => 'Ousmane',
            'telephone_gerant' => '+221770000001',
            'email_gerant' => 'ousmane.sow@gp.sn',
            'mot_de_passe' => 'password123',
            'nom_entreprise' => 'Dakar Express GP',
            'telephone_entreprise' => '+221338000000',
            'email_entreprise' => 'contact@dakarexpress.sn',
            'adresse' => 'Avenue Cheikh Anta Diop',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
            'ninea' => '9988776655',
            'registre_commerce' => 'SN-DKR-2026-B-1234',
        ];

        $response = $this->postJson('/api/auth/entreprise/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'access_token',
                'entreprise' => ['id', 'nom', 'ninea', 'registre_commerce'],
            ]);

        $this->assertDatabaseHas('entreprises', [
            'nom' => 'Dakar Express GP',
            'ninea' => '9988776655',
            'registre_commerce' => 'SN-DKR-2026-B-1234',
        ]);
    }

    public function test_gerant_can_create_agent_directly(): void
    {
        // 1. Inscrire l'entreprise
        $register = $this->postJson('/api/auth/entreprise/register', [
            'nom_gerant' => 'Diallo',
            'prenom_gerant' => 'Mamadou',
            'telephone_gerant' => '+221771112233',
            'email_gerant' => 'mamadou.diallo@gp.sn',
            'mot_de_passe' => 'password123',
            'nom_entreprise' => 'Sahel GP Transport',
            'telephone_entreprise' => '+221338112233',
            'email_entreprise' => 'contact@sahelgp.sn',
            'adresse' => 'Bourguiba',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $token = $register->json('access_token');

        // 2. Créer un agent directement par le gérant
        $agentPayload = [
            'nom' => 'Diop',
            'prenom' => 'Amadou',
            'telephone' => '+221779998877',
            'email' => 'amadou.diop@sahelgp.sn',
            'mot_de_passe' => 'agentpassword',
            'matricule' => 'AG-SAHEL-01',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/entreprise/agents/direct-create', $agentPayload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('agent_gps', [
            'matricule' => 'AG-SAHEL-01',
            'statut' => 'actif',
        ]);
    }

    public function test_gerant_can_invite_agent_via_whatsapp(): void
    {
        $register = $this->postJson('/api/auth/entreprise/register', [
            'nom_gerant' => 'Faye',
            'prenom_gerant' => 'Fatou',
            'telephone_gerant' => '+221774445566',
            'email_gerant' => 'fatou.faye@gp.sn',
            'mot_de_passe' => 'password123',
            'nom_entreprise' => 'Fatou Cargo Services',
            'telephone_entreprise' => '+221338445566',
            'email_entreprise' => 'contact@fatoucargo.sn',
            'adresse' => 'Point E',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $token = $register->json('access_token');

        $invitePayload = [
            'canal' => 'whatsapp',
            'telephone' => '+221775556677',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/entreprise/agents/invite', $invitePayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'invitation' => ['id', 'canal', 'token', 'lien_invitation'],
                'whatsapp_link',
            ]);

        $this->assertDatabaseHas('invitations_agents', [
            'canal' => 'whatsapp',
            'telephone' => '+221775556677',
        ]);
    }

    public function test_entreprise_email_verification(): void
    {
        $register = $this->postJson('/api/auth/entreprise/register', [
            'nom_gerant' => 'Kane',
            'prenom_gerant' => 'Moussa',
            'telephone_gerant' => '+221776667788',
            'email_gerant' => 'moussa.kane@gp.sn',
            'mot_de_passe' => 'password123',
            'nom_entreprise' => 'Teranga Freight GP',
            'telephone_entreprise' => '+221338667788',
            'email_entreprise' => 'contact@terangafreight.sn',
            'adresse' => 'Medina',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $tokenVerification = $register->json('entreprise.verification_token');

        $this->assertNotNull($tokenVerification);

        // Vérifier l'email via le token
        $response = $this->getJson('/api/auth/verify-entreprise/'.$tokenVerification);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('entreprises', [
            'nom' => 'Teranga Freight GP',
            'statut_verification' => 'verifiee',
        ]);
    }

    public function test_entreprise_soft_delete_corbeille_and_restore(): void
    {
        $register = $this->postJson('/api/auth/entreprise/register', [
            'nom_gerant' => 'Sy',
            'prenom_gerant' => 'Abdoulaye',
            'telephone_gerant' => '+221778889900',
            'email_gerant' => 'abdou.sy@gp.sn',
            'mot_de_passe' => 'password123',
            'nom_entreprise' => 'Sy Transports GP',
            'telephone_entreprise' => '+221338889900',
            'email_entreprise' => 'contact@sytransports.sn',
            'adresse' => 'Parcelles Assainies',
            'ville' => 'Dakar',
            'pays' => 'Sénégal',
        ]);

        $authToken = $register->json('access_token');
        $entrepriseId = $register->json('entreprise.id');

        $entreprise = Entreprise::find($entrepriseId);
        $entreprise->delete(); // Soft delete (mise à la corbeille)

        $this->assertSoftDeleted('entreprises', ['id' => $entrepriseId]);

        // Consulter la corbeille
        $trashResponse = $this->withHeader('Authorization', "Bearer {$authToken}")
            ->getJson('/api/entreprise/trash');

        $trashResponse->assertStatus(200)
            ->assertJsonCount(1, 'corbeille');

        // Restaurer l'entreprise depuis la corbeille
        $restoreResponse = $this->withHeader('Authorization', "Bearer {$authToken}")
            ->postJson("/api/entreprise/{$entrepriseId}/restore");

        $restoreResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('entreprises', [
            'id' => $entrepriseId,
            'deleted_at' => null,
        ]);
    }
}
