<?php

namespace Database\Seeders;

use App\Models\Colis;
use App\Models\Suivi_colis;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuiviColisSeeder extends Seeder
{
    public function run(): void
    {
        $colis1 = Colis::where('numero_suivi', 'SUIVI-AMADOU-001')->first();
        $voyageur1User = User::where('email', 'cheikh.fall@example.com')->first();
        $client1User = User::where('email', 'amadou.sow@example.com')->first();

        Suivi_colis::firstOrCreate([
            'colis_id' => $colis1->id,
            'statut' => 'demande_envoyee',
            'date_changement' => now()->subDays(2),
            'commentaire' => 'Demande de réservation envoyée par le client.',
            'mis_a_jour_par' => $client1User->id,
        ]);

        Suivi_colis::firstOrCreate([
            'colis_id' => $colis1->id,
            'statut' => 'reservation_acceptee',
            'date_changement' => now()->subDays(1),
            'commentaire' => 'Demande acceptée par le voyageur Cheikh Fall.',
            'mis_a_jour_par' => $voyageur1User->id,
        ]);

        Suivi_colis::firstOrCreate([
            'colis_id' => $colis1->id,
            'statut' => 'colis_depose',
            'date_changement' => now()->subHours(12),
            'commentaire' => 'Colis réceptionné au Relais Dépôt Paris.',
            'mis_a_jour_par' => $voyageur1User->id,
        ]);
    }
}
