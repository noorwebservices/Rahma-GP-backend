<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdresseDepotController;
use App\Http\Controllers\Api\AdresseRecuperationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\VoyageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (préfixe /api)
|--------------------------------------------------------------------------
*/

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

    // Routes Administration (Réservées au rôle Admin)
    Route::prefix('admin')->middleware('role:admin,api')->group(function () {
        Route::get('users', [AdminController::class, 'users']);
    });
});
