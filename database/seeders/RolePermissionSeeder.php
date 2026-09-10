<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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

        $permissions = [
            'profil.voir',
            'profil.modifier',
            'mode.basculer',

            'voyages.creer',
            'voyages.modifier',
            'voyages.supprimer',
            'voyages.voir',
            'voyages.publier',
            'voyages.gerer',

            'reservations.creer',
            'reservations.voir',
            'reservations.modifier',
            'reservations.accepter',
            'reservations.refuser',
            'reservations.annuler',
            'reservations.gerer',

            'colis.creer',
            'colis.voir',
            'colis.modifier',
            'colis.modifier_statut',
            'colis.suivre',
            'colis.annuler',
            'colis.gerer',

            'suivis.creer',
            'suivis.voir',

            'destinataires.creer',
            'destinataires.voir',
            'destinataires.modifier',
            'destinataires.supprimer',

            'adresse_depots.creer',
            'adresse_depots.voir',
            'adresse_depots.modifier',
            'adresse_depots.supprimer',

            'adresse_recuperations.creer',
            'adresse_recuperations.voir',
            'adresse_recuperations.modifier',
            'adresse_recuperations.supprimer',

            'paiements.creer',
            'paiements.voir',
            'paiements.confirmer',
            'paiements.gerer',

            'messages.envoyer',
            'messages.voir',

            'evaluations.creer',
            'evaluations.voir',
            'evaluations.modifier',
            'evaluations.supprimer',
            'evaluations.gerer',

            'revenus.voir',
            'revenus.retirer',

            'notifications.voir',
            'notifications.marquer_lue',
            'notifications.gerer',

            'utilisateurs.gerer',
            'utilisateurs.suspendre',
            'statistiques.voir',
        ];

        $guards = ['api', 'web'];

        foreach ($guards as $guard) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard,
                ]);
            }

            $clientPermissions = [
                'profil.voir', 'profil.modifier', 'mode.basculer', 'voyages.voir',
                'reservations.creer', 'reservations.voir', 'reservations.modifier', 'reservations.annuler',
                'colis.creer', 'colis.voir', 'colis.modifier', 'colis.suivre', 'colis.annuler',
                'suivis.voir', 'destinataires.creer', 'destinataires.voir', 'destinataires.modifier', 'destinataires.supprimer',
                'adresse_depots.voir', 'adresse_recuperations.voir',
                'paiements.creer', 'paiements.voir', 'messages.envoyer', 'messages.voir',
                'evaluations.creer', 'evaluations.voir', 'notifications.voir', 'notifications.marquer_lue',
            ];

            $client = Role::firstOrCreate([
                'name' => 'client',
                'guard_name' => $guard,
            ]);
            $client->syncPermissions(Permission::where('guard_name', $guard)->whereIn('name', $clientPermissions)->get());

            $voyageurPermissions = [
                'profil.voir', 'profil.modifier', 'mode.basculer',
                'voyages.creer', 'voyages.modifier', 'voyages.supprimer', 'voyages.voir', 'voyages.publier',
                'reservations.voir', 'reservations.accepter', 'reservations.refuser',
                'colis.voir', 'colis.modifier_statut', 'suivis.creer', 'suivis.voir',
                'adresse_depots.creer', 'adresse_depots.voir', 'adresse_depots.modifier', 'adresse_depots.supprimer',
                'adresse_recuperations.creer', 'adresse_recuperations.voir', 'adresse_recuperations.modifier', 'adresse_recuperations.supprimer',
                'paiements.voir', 'messages.envoyer', 'messages.voir',
                'evaluations.creer', 'evaluations.voir', 'revenus.voir', 'revenus.retirer',
                'notifications.voir', 'notifications.marquer_lue',
            ];

            $voyageur = Role::firstOrCreate([
                'name' => 'voyageur',
                'guard_name' => $guard,
            ]);
            $voyageur->syncPermissions(Permission::where('guard_name', $guard)->whereIn('name', $voyageurPermissions)->get());

            $admin = Role::firstOrCreate([
                'name' => 'admin',
                'guard_name' => $guard,
            ]);
            $admin->syncPermissions(Permission::where('guard_name', $guard)->get());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
