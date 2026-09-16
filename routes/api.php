<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdresseDepotController;
use App\Http\Controllers\Api\AdresseRecuperationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ColisController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RevenusVoyageurController;
use App\Http\Controllers\Api\VoyageController;
use App\Http\Controllers\Api\WavePaymentController;
use App\Http\Controllers\Api\DemandePartenariatController;
use App\Http\Controllers\Api\SignalementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (préfixe /api)
|--------------------------------------------------------------------------
*/

// Formulaire public de demande de partenariat (portail)
Route::post('demandes-partenariat', [DemandePartenariatController::class, 'store']);

// Suivi public de colis
Route::get('colis/suivi/{numero_suivi}', [ColisController::class, 'suiviPublic']);

// Évaluations publiques d'un voyageur
Route::get('voyageurs/{voyageur}/evaluations', [EvaluationController::class, 'indexForVoyageur']);

// Routes publiques d'authentification
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Routes protégées par JWT (auth:api)
Route::middleware('auth:api')->group(function () {

    // Authentification & Session utilisateur
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Gestion du Profil Utilisateur
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('client', [ProfileController::class, 'createClientProfile']);
        Route::post('voyageur', [ProfileController::class, 'createVoyageurProfile']);

        // Basculement de mode sécurisé par la permission mode.basculer
        Route::post('toggle-mode', [ProfileController::class, 'toggleMode'])
            ->middleware('permission:mode.basculer');
    });

    // Adresses de dépôt
    Route::get('adresse-depots', [AdresseDepotController::class, 'index'])
        ->middleware('permission:adresse_depots.voir,api');
    Route::post('adresse-depots', [AdresseDepotController::class, 'store'])
        ->middleware('permission:adresse_depots.creer,api');
    Route::get('adresse-depots/{adresseDepot}', [AdresseDepotController::class, 'show'])
        ->middleware('permission:adresse_depots.voir,api');
    Route::put('adresse-depots/{adresseDepot}', [AdresseDepotController::class, 'update'])
        ->middleware('permission:adresse_depots.modifier,api');
    Route::patch('adresse-depots/{adresseDepot}', [AdresseDepotController::class, 'update'])
        ->middleware('permission:adresse_depots.modifier,api');
    Route::delete('adresse-depots/{adresseDepot}', [AdresseDepotController::class, 'destroy'])
        ->middleware('permission:adresse_depots.supprimer,api');

    // Adresses de récupération
    Route::get('adresse-recuperations', [AdresseRecuperationController::class, 'index'])
        ->middleware('permission:adresse_recuperations.voir,api');
    Route::post('adresse-recuperations', [AdresseRecuperationController::class, 'store'])
        ->middleware('permission:adresse_recuperations.creer,api');
    Route::get('adresse-recuperations/{adresseRecuperation}', [AdresseRecuperationController::class, 'show'])
        ->middleware('permission:adresse_recuperations.voir,api');
    Route::put('adresse-recuperations/{adresseRecuperation}', [AdresseRecuperationController::class, 'update'])
        ->middleware('permission:adresse_recuperations.modifier,api');
    Route::patch('adresse-recuperations/{adresseRecuperation}', [AdresseRecuperationController::class, 'update'])
        ->middleware('permission:adresse_recuperations.modifier,api');
    Route::delete('adresse-recuperations/{adresseRecuperation}', [AdresseRecuperationController::class, 'destroy'])
        ->middleware('permission:adresse_recuperations.supprimer,api');

    // Gestion des Voyages
    Route::get('voyages', [VoyageController::class, 'index'])
        ->middleware('permission:voyages.voir,api');
    Route::post('voyages', [VoyageController::class, 'store'])
        ->middleware('permission:voyages.creer,api');
    Route::get('voyages/{voyage}', [VoyageController::class, 'show'])
        ->middleware('permission:voyages.voir,api');
    Route::put('voyages/{voyage}', [VoyageController::class, 'update'])
        ->middleware('permission:voyages.modifier,api');
    Route::patch('voyages/{voyage}', [VoyageController::class, 'update'])
        ->middleware('permission:voyages.modifier,api');
    Route::delete('voyages/{voyage}', [VoyageController::class, 'destroy'])
        ->middleware('permission:voyages.supprimer,api');
    Route::post('voyages/{voyage}/publier', [VoyageController::class, 'publier'])
        ->middleware('permission:voyages.publier,api');
    Route::post('voyages/{voyage}/annuler', [VoyageController::class, 'annuler'])
        ->middleware('permission:voyages.modifier,api');

    // Gestion des Réservations
    Route::get('reservations', [ReservationController::class, 'index'])
        ->middleware('permission:reservations.voir,api');
    Route::post('reservations', [ReservationController::class, 'store'])
        ->middleware('permission:reservations.creer,api');
    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])
        ->middleware('permission:reservations.voir,api');
    Route::put('reservations/{reservation}', [ReservationController::class, 'update'])
        ->middleware('permission:reservations.modifier,api');
    Route::patch('reservations/{reservation}', [ReservationController::class, 'update'])
        ->middleware('permission:reservations.modifier,api');
    Route::post('reservations/{reservation}/accepter', [ReservationController::class, 'accepter'])
        ->middleware('permission:reservations.accepter,api');
    Route::post('reservations/{reservation}/refuser', [ReservationController::class, 'refuser'])
        ->middleware('permission:reservations.refuser,api');
    Route::post('reservations/{reservation}/annuler', [ReservationController::class, 'annuler'])
        ->middleware('permission:reservations.annuler,api');
    Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])
        ->middleware('permission:reservations.annuler,api');

    // Messagerie / Discussions par Réservation
    Route::get('reservations/{reservation}/messages', [MessageController::class, 'indexByReservation'])
        ->middleware('permission:messages.voir,api');
    Route::post('reservations/{reservation}/messages', [MessageController::class, 'store'])
        ->middleware('permission:messages.envoyer,api');
    Route::get('messages/non-lus-count', [MessageController::class, 'unreadCount'])
        ->middleware('permission:messages.voir,api');

    // Évaluations
    Route::post('reservations/{reservation}/evaluations', [EvaluationController::class, 'store'])
        ->middleware('permission:evaluations.creer,api');
    Route::get('evaluations', [EvaluationController::class, 'index'])
        ->middleware('permission:evaluations.voir,api');

    // Gestion des Colis
    Route::get('colis', [ColisController::class, 'index'])
        ->middleware('permission:colis.voir,api');
    Route::post('colis', [ColisController::class, 'store'])
        ->middleware('permission:colis.creer,api');
    Route::get('colis/{colis}', [ColisController::class, 'show'])
        ->middleware('permission:colis.voir,api');
    Route::put('colis/{colis}', [ColisController::class, 'update'])
        ->middleware('permission:colis.modifier,api');
    Route::patch('colis/{colis}', [ColisController::class, 'update'])
        ->middleware('permission:colis.modifier,api');
    Route::patch('colis/{colis}/statut', [ColisController::class, 'updateStatut'])
        ->middleware('permission:colis.modifier_statut,api');

    // Signalement de compte utilisateur (Clients & Voyageurs)
    Route::post('signalements', [SignalementController::class, 'store']);

    // Routes Administration (Réservées au rôle Admin)
    Route::prefix('admin')->middleware('role:admin,api')->group(function () {
        Route::get('dashboard-stats', [AdminController::class, 'dashboardStats']);
        Route::get('users', [AdminController::class, 'users']);
        Route::get('users/{user}', [AdminController::class, 'showUser']);
        Route::patch('users/{user}/block', [AdminController::class, 'toggleBlockUser']);
        Route::patch('voyageurs/{voyageur}/statut', [AdminController::class, 'updateStatutVoyageur']);
        Route::put('voyageurs/{voyageur}/statut', [AdminController::class, 'updateStatutVoyageur']);
        Route::get('signalements', [AdminController::class, 'signalements']);
        Route::patch('signalements/{signalement}/statut', [AdminController::class, 'updateSignalementStatut']);
        Route::get('demandes-partenariat', [AdminController::class, 'demandesPartenariat']);
        Route::patch('demandes-partenariat/{demande}/statut', [AdminController::class, 'updateDemandePartenariatStatut']);
        Route::get('voyageurs-stats', [AdminController::class, 'voyageursStats']);
    });

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/non-lus-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/lue', [NotificationController::class, 'marquerLue']);
    Route::patch('notifications/toutes-lues', [NotificationController::class, 'marquerToutesLues']);

    // Paiements
    Route::post('reservations/{reservation}/paiements', [PaiementController::class, 'store'])
        ->middleware('permission:reservations.voir,api');
    Route::get('paiements', [PaiementController::class, 'index'])
        ->middleware('permission:reservations.voir,api');
    Route::get('paiements/{paiement}', [PaiementController::class, 'show'])
        ->middleware('permission:reservations.voir,api');

    // Integration Wave Checkout API
    Route::post('reservations/{reservation}/pay-wave', [WavePaymentController::class, 'initiatePayment']);
    Route::get('reservations/{reservation}/wave-status', [WavePaymentController::class, 'checkStatus']);

    // Revenus Voyageur
    Route::get('revenus', [RevenusVoyageurController::class, 'index']);
    Route::post('revenus/retrait', [RevenusVoyageurController::class, 'retirer']);

    
});

// Webhook Wave (Public, vérifié par signature HMAC)
Route::post('wave/webhook', [WavePaymentController::class, 'handleWebhook']);
