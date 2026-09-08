<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
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
});
