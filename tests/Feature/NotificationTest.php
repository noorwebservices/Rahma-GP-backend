<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(): User
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $this->withHeader('Authorization', "Bearer {$token}");

        return $user;
    }

    #[Test]
    public function utilisateur_peut_lister_ses_notifications(): void
    {
        $user = $this->loginAs();

        Notification::factory()->count(3)->create(['user_id' => $user->id]);
        // Notifications d'un autre utilisateur (ne doivent pas apparaître)
        Notification::factory()->count(2)->create();

        $response = $this->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function utilisateur_peut_filtrer_notifications_non_lues(): void
    {
        $user = $this->loginAs();

        Notification::factory()->count(2)->create(['user_id' => $user->id, 'lu' => false]);
        Notification::factory()->lue()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/notifications?unread=true');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function utilisateur_peut_obtenir_le_compte_des_non_lues(): void
    {
        $user = $this->loginAs();

        Notification::factory()->count(4)->create(['user_id' => $user->id, 'lu' => false]);
        Notification::factory()->lue()->count(1)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/notifications/non-lus-count');

        $response->assertOk()
            ->assertJson(['unread_count' => 4]);
    }

    #[Test]
    public function utilisateur_peut_marquer_une_notification_comme_lue(): void
    {
        $user = $this->loginAs();
        $notification = Notification::factory()->create(['user_id' => $user->id, 'lu' => false]);

        $response = $this->patchJson("/api/notifications/{$notification->id}/lue");

        $response->assertOk();
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'lu' => true]);
    }

    #[Test]
    public function utilisateur_ne_peut_pas_marquer_la_notification_d_un_autre(): void
    {
        $this->loginAs();
        $autreNotification = Notification::factory()->create(['lu' => false]);

        $response = $this->patchJson("/api/notifications/{$autreNotification->id}/lue");

        $response->assertForbidden();
    }

    #[Test]
    public function utilisateur_peut_marquer_toutes_ses_notifications_comme_lues(): void
    {
        $user = $this->loginAs();
        Notification::factory()->count(5)->create(['user_id' => $user->id, 'lu' => false]);

        $response = $this->patchJson('/api/notifications/toutes-lues');

        $response->assertOk();
        $this->assertDatabaseMissing('notifications', ['user_id' => $user->id, 'lu' => false]);
    }

    #[Test]
    public function notifications_de_plus_de_3_jours_sont_supprimees_par_prune(): void
    {
        $user = User::factory()->create();

        // Notification de 4 jours (doit être supprimée)
        $ancienneNotif = Notification::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(4),
        ]);

        // Notification récente de 1 jour (doit être conservée)
        $recenteNotif = Notification::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(1),
        ]);

        $this->artisan('model:prune', ['--model' => [Notification::class]]);

        $this->assertDatabaseMissing('notifications', ['id' => $ancienneNotif->id]);
        $this->assertDatabaseHas('notifications', ['id' => $recenteNotif->id]);
    }
}
