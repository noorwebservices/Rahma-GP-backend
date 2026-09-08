<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Exécution de l'ensemble des seeders de l'application dans l'ordre logique de dépendance.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ClientSeeder::class,
            VoyageurSeeder::class,
            AdresseDepotSeeder::class,
            AdresseRecuperationSeeder::class,
            VoyageSeeder::class,
            ReservationSeeder::class,
            ColisSeeder::class,
            SuiviColisSeeder::class,
            PaiementSeeder::class,
            MessageSeeder::class,
            EvaluationSeeder::class,
            RevenusVoyageurSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
