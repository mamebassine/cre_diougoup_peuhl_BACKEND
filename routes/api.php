
<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\ApprenantController;
use App\Http\Controllers\Api\DiplomeAttestationController;
use App\Http\Controllers\Api\BoiteIdeeController;


Route::prefix('auth')->group(function(){

    Route::post('/register',[AuthController::class,'register']);
    Route::post('/login',[AuthController::class,'login']);

    Route::middleware('auth:api')->group(function(){

        Route::get('/profile',[AuthController::class,'profile']);
        Route::post('/logout',[AuthController::class,'logout']);

    
    // APPRENANTS
    Route::get('/apprenants',[ApprenantController::class,'index']);
    Route::post('/apprenants',[ApprenantController::class,'store']);
    Route::get('/apprenants/{id}',[ApprenantController::class,'show']);
    Route::put('/apprenants/{id}',[ApprenantController::class,'update']);
    Route::delete('/apprenants/{id}',[ApprenantController::class,'destroy']);

    // DIPLOMES
    Route::get('/diplomes',[DiplomeAttestationController::class,'index']);
    Route::post('/diplomes',[DiplomeAttestationController::class,'store']);
    Route::get('/diplomes/{id}',[DiplomeAttestationController::class,'show']);
    Route::put('/diplomes/{id}',[DiplomeAttestationController::class,'update']);
    Route::delete('/diplomes/{id}',[DiplomeAttestationController::class,'destroy']);

    // BOITE IDEES
    Route::get('/boite-idees',[BoiteIdeeController::class,'index']);
    Route::post('/boite-idees',[BoiteIdeeController::class,'store']);
    Route::get('/boite-idees/{id}',[BoiteIdeeController::class,'show']);
    Route::put('/boite-idees/{id}',[BoiteIdeeController::class,'update']);
    Route::delete('/boite-idees/{id}',[BoiteIdeeController::class,'destroy']);

});


});