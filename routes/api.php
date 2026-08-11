<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApprenantController;
use App\Http\Controllers\Api\DiplomeAttestationController;
use App\Http\Controllers\Api\BoiteIdeeController;
use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\InscriptionController;


Route::prefix('auth')->group(function () {


    /*
    |--------------------------------------------------------------------------
    | AUTHENTIFICATION
    |--------------------------------------------------------------------------
    */

    // Routes publiques

    Route::post('/register', [
        AuthController::class,
        'register'
    ]);

    Route::post('/login', [
        AuthController::class,
        'login'
    ]);


    /*
    |--------------------------------------------------------------------------
    | FORMATIONS PUBLIQUES
    |--------------------------------------------------------------------------
    */

    Route::get('/formations', [
        FormationController::class,
        'index'
    ]);

    Route::get('/formations/{id}', [
        FormationController::class,
        'show'
    ]);


    /*
    |--------------------------------------------------------------------------
    | INSCRIPTION PUBLIQUE À UNE FORMATION
    |--------------------------------------------------------------------------
    |
    | Cette route permet à un visiteur :
    | - de créer son compte
    | - de créer son dossier apprenant
    | - de s'inscrire à une formation
    |
    | Elle est volontairement en dehors de auth:api.
    |icii a revoir ou enlever
    */

    Route::post('/inscription-formation', [
        InscriptionController::class,
        'inscriptionPublique'
    ]);


    /*
    |--------------------------------------------------------------------------
    | ROUTES PROTEGEES JWT
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:api')->group(function () {


        Route::get('/profile', [
            AuthController::class,
            'profile'
        ]);

        Route::post('/logout', [
            AuthController::class,
            'logout'
        ]);


        /*
        |--------------------------------------------------------------------------
        | APPRENANTS
        |--------------------------------------------------------------------------
        */

        // Apprenant crée son dossier

        Route::middleware('role:apprenant,admin,gestionnaire')->group(function () {

            Route::post('/apprenants', [
                ApprenantController::class,
                'store'
            ]);

            Route::get('/mon-profil-apprenant', [
                ApprenantController::class,
                'monProfil'
            ]);

            Route::put('/mon-profil-apprenant', [
                ApprenantController::class,
                'updateMonProfil'
            ]);

        });


        // Gestion apprenants par admin/gestionnaire

        Route::middleware('role:admin,gestionnaire')->group(function () {

            Route::get('/apprenants', [
                ApprenantController::class,
                'index'
            ]);

            Route::get('/apprenants/{id}', [
                ApprenantController::class,
                'show'
            ]);

            Route::put('/apprenants/{id}', [
                ApprenantController::class,
                'update'
            ]);

            Route::delete('/apprenants/{id}', [
                ApprenantController::class,
                'destroy'
            ]);

        });


        /*
        |--------------------------------------------------------------------------
        | FORMATIONS
        |--------------------------------------------------------------------------
        */

        // Gestion formations admin / gestionnaire

        Route::middleware('role:admin,gestionnaire')->group(function () {

            Route::post('/formations', [
                FormationController::class,
                'store'
            ]);

            Route::put('/formations/{id}', [
                FormationController::class,
                'update'
            ]);

            Route::delete('/formations/{id}', [
                FormationController::class,
                'destroy'
            ]);

        });


        /*
        |--------------------------------------------------------------------------
        | INSCRIPTIONS FORMATIONS
        |--------------------------------------------------------------------------
        */

        // Inscription directe par admin / gestionnaire

        Route::middleware('role:admin,gestionnaire')->group(function () {

            Route::post('/inscriptions/admin', [
                InscriptionController::class,
                'inscriptionAdmin'
            ]);

        });


        // L'apprenant demande une inscription

        Route::middleware('role:apprenant')->group(function () {

            Route::post('/inscriptions', [
                InscriptionController::class,
                'store'
            ]);

        });


        // Voir les inscriptions

        Route::middleware('role:apprenant,admin,gestionnaire')->group(function () {

            Route::get('/inscriptions', [
                InscriptionController::class,
                'index'
            ]);

            Route::get('/inscriptions/{id}', [
                InscriptionController::class,
                'show'
            ]);

        });


        // Validation inscription

        Route::middleware('role:admin,gestionnaire')->group(function () {

            Route::put('/inscriptions/{id}', [
                InscriptionController::class,
                'update'
            ]);

            Route::delete('/inscriptions/{id}', [
                InscriptionController::class,
                'destroy'
            ]);

        });


        /*
        |--------------------------------------------------------------------------
        | DIPLÔMES / ATTESTATIONS
        |--------------------------------------------------------------------------
        */

        // Apprenant

        Route::middleware('role:apprenant')->group(function () {

            Route::post('/diplomes/demande', [
                DiplomeAttestationController::class,
                'demande'
            ]);

            Route::get('/diplomes/mes-demandes', [
                DiplomeAttestationController::class,
                'mesDemandes'
            ]);

        });


        // Admin / Gestionnaire

        Route::middleware('role:admin,gestionnaire')->group(function () {

            Route::get('/diplomes', [
                DiplomeAttestationController::class,
                'index'
            ]);

            Route::post('/diplomes', [
                DiplomeAttestationController::class,
                'store'
            ]);

            Route::get('/diplomes/{id}', [
                DiplomeAttestationController::class,
                'show'
            ]);

            Route::put('/diplomes/{id}', [
                DiplomeAttestationController::class,
                'update'
            ]);

            Route::delete('/diplomes/{id}', [
                DiplomeAttestationController::class,
                'destroy'
            ]);

        });


        /*
        |--------------------------------------------------------------------------
        | BOITE A IDEES
        |--------------------------------------------------------------------------
        */

        // Tous les rôles

        Route::middleware('role:apprenant,admin,gestionnaire')->group(function () {

            Route::get('/boite-idees', [
                BoiteIdeeController::class,
                'index'
            ]);

            Route::get('/boite-idees/{id}', [
                BoiteIdeeController::class,
                'show'
            ]);

            Route::put('/boite-idees/{id}', [
                BoiteIdeeController::class,
                'update'
            ]);

        });


        // Apprenant seulement

        Route::middleware('role:apprenant')->group(function () {

            Route::post('/boite-idees', [
                BoiteIdeeController::class,
                'store'
            ]);

        });


        // Admin / Gestionnaire

        Route::middleware('role:admin,gestionnaire')->group(function () {

            Route::delete('/boite-idees/{id}', [
                BoiteIdeeController::class,
                'destroy'
            ]);

        });


    });

});