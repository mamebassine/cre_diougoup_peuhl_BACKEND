<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApprenantController;
use App\Http\Controllers\Api\DiplomeAttestationController;
use App\Http\Controllers\Api\BoiteIdeeController;

/*
|--------------------------------------------------------------------------
| AUTHENTIFICATION
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    // 🟢 PUBLIC
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // 🔐 PROTÉGÉ
    Route::middleware('auth:api')->group(function () {

        // Profil
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | APPRENANTS
        |--------------------------------------------------------------------------
        */

        // 👤 Apprenant peut s'inscrire / créer son profil
        Route::middleware('role:apprenant')->group(function () {
            Route::post('/apprenants', [ApprenantController::class, 'store']);
        });

        // 👨‍💼 Admin + Gestionnaire gèrent les apprenants
        Route::middleware('role:admin,gestionnaire')->group(function () {

            Route::get('/apprenants', [ApprenantController::class, 'index']);
            Route::get('/apprenants/{id}', [ApprenantController::class, 'show']);
            Route::put('/apprenants/{id}', [ApprenantController::class, 'update']);
            Route::delete('/apprenants/{id}', [ApprenantController::class, 'destroy']);

        });

        /*
        |--------------------------------------------------------------------------
        | DIPLÔMES (LOGIQUE CORRIGÉE)
        |--------------------------------------------------------------------------
        */

        // 👤 APPRENANT : fait une demande de diplôme
        Route::middleware('role:apprenant')->group(function () {
            Route::post('/diplomes/demande', [DiplomeAttestationController::class, 'demande']);
            Route::get('/diplomes/mes-demandes', [DiplomeAttestationController::class, 'mesDemandes']);
        });

        // 👨‍💼 ADMIN + GESTIONNAIRE : validation + gestion
        Route::middleware('role:admin,gestionnaire')->group(function () {

            Route::get('/diplomes', [DiplomeAttestationController::class, 'index']);
            Route::post('/diplomes', [DiplomeAttestationController::class, 'store']);

            Route::get('/diplomes/{id}', [DiplomeAttestationController::class, 'show']);
            Route::put('/diplomes/{id}', [DiplomeAttestationController::class, 'update']);
            Route::delete('/diplomes/{id}', [DiplomeAttestationController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | BOÎTE À IDÉES
        |--------------------------------------------------------------------------
        */

        // 👤 APPRENANT : envoyer une idée
        Route::middleware('role:apprenant')->group(function () {
            Route::post('/boite-idees', [BoiteIdeeController::class, 'store']);
        });

        // 👨‍💼 ADMIN + GESTIONNAIRE : gérer les idées
        Route::middleware('role:admin,gestionnaire')->group(function () {
            Route::get('/boite-idees', [BoiteIdeeController::class, 'index']);
            Route::get('/boite-idees/{id}', [BoiteIdeeController::class, 'show']);
            Route::put('/boite-idees/{id}', [BoiteIdeeController::class, 'update']);
            Route::delete('/boite-idees/{id}', [BoiteIdeeController::class, 'destroy']);
        });

    });

});