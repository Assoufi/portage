<?php
// routes/web.php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ConsultantController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\MissionController;
use Illuminate\Support\Facades\Route;

// Routes publiques (authentification)
Route::get('/', function () {
    return view('welcome');
});

// Routes d'authentification (Breeze)
require __DIR__.'/auth.php';

// Routes protégées par authentification
Route::middleware(['auth'])->group(function () {
    
    // Dashboard principal
    Route::get('/dashboard', [MissionController::class, 'dashboard'])
        ->name('dashboard');
    
    // Routes pour les Consultants
    Route::resource('consultants', ConsultantController::class);
    Route::get('consultants/{consultant}/export', [ConsultantController::class, 'export'])
        ->name('consultants.export');
    
    // Routes pour les Clients
    Route::resource('clients', ClientController::class);
    Route::get('clients/check-ice', [ClientController::class, 'checkIce'])
        ->name('clients.check-ice');
    
    // Routes pour les Fournisseurs
    Route::resource('fournisseurs', FournisseurController::class);
    
    // Routes pour les Missions
    Route::resource('missions', MissionController::class);
    Route::post('missions/calculate-prix-vente', [MissionController::class, 'calculatePrixVente'])
        ->name('missions.calculate-prix-vente');
    Route::get('missions/dashboard', [MissionController::class, 'dashboard'])
        ->name('missions.dashboard');
    
    // Route pour le profil utilisateur (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Routes API pour les appels AJAX (optionnel)
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/consultants/search', [ConsultantController::class, 'search']);
    Route::get('/clients/search', [ClientController::class, 'search']);
    Route::get('/fournisseurs/search', [FournisseurController::class, 'search']);
});