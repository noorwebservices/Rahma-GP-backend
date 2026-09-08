<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Rôles de l'application :
     * - client
     * - voyageur
     * - admin
     *
     * Un même utilisateur peut avoir les rôles "client" ET "voyageur".
     */
    public function run(): void
    {
        // Réinitialiser le cache des permissions de Spatie.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [

            // -----------------------------------------------------------------
            // Profil utilisateur
            // -----------------------------------------------------------------

            'profil.voir',
            'profil.modifier',

            // -----------------------------------------------------------------
            // Voyages
            // -----------------------------------------------------------------

            'voyages.creer',
            'voyages.modifier',
            'voyages.supprimer',
            'voyages.voir',
            'voyages.publier',
            'voyages.gerer',

            // -----------------------------------------------------------------
            // Réservations
            // -----------------------------------------------------------------

            'reservations.creer',
            'reservations.voir',
            'reservations.modifier',
            'reservations.accepter',
            'reservations.refuser',
            'reservations.annuler',
            'reservations.gerer',

            // -----------------------------------------------------------------
            // Colis
            // -----------------------------------------------------------------

            'colis.creer',
            'colis.voir',
            'colis.modifier',
            'colis.modifier_statut',
            'colis.suivre',
            'colis.annuler',
            'colis.gerer',

            // -----------------------------------------------------------------
            // Suivi des colis
            // -----------------------------------------------------------------

            'suivis.creer',
            'suivis.voir',

            // -----------------------------------------------------------------
            // Destinataires
            // -----------------------------------------------------------------

            'destinataires.creer',
            'destinataires.voir',
            'destinataires.modifier',
            'destinataires.supprimer',

            // -----------------------------------------------------------------
            // Paiements
            // -----------------------------------------------------------------

            'paiements.creer',
            'paiements.voir',
            'paiements.confirmer',
            'paiements.gerer',

            // -----------------------------------------------------------------
            // Messagerie
            // -----------------------------------------------------------------

            'messages.envoyer',
            'messages.voir',

            // -----------------------------------------------------------------
            // Évaluations
            // -----------------------------------------------------------------

            'evaluations.creer',
            'evaluations.voir',
            'evaluations.modifier',
            'evaluations.supprimer',
            'evaluations.gerer',

            // -----------------------------------------------------------------
            // Revenus voyageur
            // -----------------------------------------------------------------

            'revenus.voir',
            'revenus.retirer',

            // -----------------------------------------------------------------
            // Notifications
            // -----------------------------------------------------------------

            'notifications.voir',
            'notifications.marquer_lue',
            'notifications.gerer',

            // -----------------------------------------------------------------
            // Administration
            // -----------------------------------------------------------------

            'utilisateurs.gerer',
            'utilisateurs.suspendre',
            'statistiques.voir',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Rôle Client
        |--------------------------------------------------------------------------
        */

        $client = Role::firstOrCreate([
            'name' => 'client',
            'guard_name' => 'web',
        ]);

        $client->syncPermissions([
            'profil.voir',
            'profil.modifier',
            'mode.basculer',

            'voyages.voir',

            'reservations.creer',
            'reservations.voir',
            'reservations.modifier',
            'reservations.annuler',

            'colis.creer',
            'colis.voir',
            'colis.modifier',
            'colis.suivre',
            'colis.annuler',

            'suivis.voir',

            'destinataires.creer',
            'destinataires.voir',
            'destinataires.modifier',
            'destinataires.supprimer',

            'paiements.creer',
            'paiements.voir',

            'messages.envoyer',
            'messages.voir',

            'evaluations.creer',
            'evaluations.voir',

            'notifications.voir',
            'notifications.marquer_lue',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Rôle Voyageur
        |--------------------------------------------------------------------------
        */

        $voyageur = Role::firstOrCreate([
            'name' => 'voyageur',
            'guard_name' => 'web',
        ]);

        $voyageur->syncPermissions([
            'profil.voir',
            'profil.modifier',
            'mode.basculer',

            'voyages.creer',
            'voyages.modifier',
            'voyages.supprimer',
            'voyages.voir',
            'voyages.publier',

            'reservations.voir',
            'reservations.accepter',
            'reservations.refuser',

            'colis.voir',
            'colis.modifier_statut',

            'suivis.creer',
            'suivis.voir',

            'paiements.voir',

            'messages.envoyer',
            'messages.voir',

            'evaluations.creer',
            'evaluations.voir',

            'revenus.voir',
            'revenus.retirer',

            'notifications.voir',
            'notifications.marquer_lue',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Rôle Admin
        |--------------------------------------------------------------------------
        */

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // L'administrateur possède toutes les permissions.
        $admin->syncPermissions(Permission::all());

        // Vider à nouveau le cache après les modifications.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
