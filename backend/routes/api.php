<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogueActeController;
use App\Http\Controllers\Api\Dentiste\DentisteDossierController;
use App\Http\Controllers\Api\Dentiste\DentisteOrdonnanceController;
use App\Http\Controllers\Api\Dentiste\DentistePlanningController;
use App\Http\Controllers\Api\Dentiste\DentistePlanTraitementController;
use App\Http\Controllers\Api\Dentiste\DentisteStatController;
use App\Http\Controllers\Api\Patient\PatientDossierController;
use App\Http\Controllers\Api\Patient\PatientFactureController;
use App\Http\Controllers\Api\Patient\PatientOrdonnanceController;
use App\Http\Controllers\Api\Patient\PatientProfilController;
use App\Http\Controllers\Api\Patient\PatientRdvController;
use App\Http\Controllers\Api\Secretaire\SecretaireFactureController;
use App\Http\Controllers\Api\Secretaire\SecretaireHoraireController;
use App\Http\Controllers\Api\Secretaire\SecretairePatientController;
use App\Http\Controllers\Api\Secretaire\SecretaireRdvController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────────────────────────────────
// Routes publiques
// ──────────────────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ──────────────────────────────────────────────────────────────────────────────
// Routes authentifiées (tous rôles)
// ──────────────────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Catalogue actes (lecture pour tous, écriture pour dentiste/secrétaire)
    Route::get('/catalogue-actes', [CatalogueActeController::class, 'index']);
    Route::middleware('role:dentiste,secretaire')->group(function () {
        Route::post('/catalogue-actes',       [CatalogueActeController::class, 'store']);
        Route::put('/catalogue-actes/{id}',   [CatalogueActeController::class, 'update']);
        Route::delete('/catalogue-actes/{id}',[CatalogueActeController::class, 'destroy']);
    });

    // ── Espace Patient ────────────────────────────────────────────────────────
    Route::middleware('role:patient')->prefix('patient')->group(function () {

        // Profil
        Route::get('/profil',             [PatientProfilController::class, 'show']);
        Route::put('/profil',             [PatientProfilController::class, 'update']);
        Route::put('/profil/password',    [PatientProfilController::class, 'updatePassword']);

        // RDV
        Route::get('/rdv',                [PatientRdvController::class, 'index']);
        Route::post('/rdv',               [PatientRdvController::class, 'store']);
        Route::get('/rdv/creneaux',       [PatientRdvController::class, 'creneaux']);
        Route::get('/rdv/{id}',           [PatientRdvController::class, 'show']);
        Route::delete('/rdv/{id}',        [PatientRdvController::class, 'destroy']);

        // Dossier médical (lecture seule)
        Route::get('/dossier',            [PatientDossierController::class, 'show']);

        // Factures
        Route::get('/factures',           [PatientFactureController::class, 'index']);
        Route::get('/factures/{id}',      [PatientFactureController::class, 'show']);

        // Ordonnances
        Route::get('/ordonnances',        [PatientOrdonnanceController::class, 'index']);
        Route::get('/ordonnances/{id}',   [PatientOrdonnanceController::class, 'show']);
    });

    // ── Espace Secrétaire ─────────────────────────────────────────────────────
    Route::middleware('role:secretaire')->prefix('secretaire')->group(function () {

        // Tableau de bord
        Route::get('/dashboard',          [SecretaireRdvController::class, 'dashboard']);

        // Gestion patients
        Route::get('/patients',           [SecretairePatientController::class, 'index']);
        Route::post('/patients',          [SecretairePatientController::class, 'store']);
        Route::get('/patients/{id}',      [SecretairePatientController::class, 'show']);
        Route::put('/patients/{id}',      [SecretairePatientController::class, 'update']);
        Route::delete('/patients/{id}',   [SecretairePatientController::class, 'destroy']);
        Route::post('/patients/{id}/restore', [SecretairePatientController::class, 'restore']);

        // Agenda / RDV
        Route::get('/rdv',                [SecretaireRdvController::class, 'index']);
        Route::get('/rdv/semaine',        [SecretaireRdvController::class, 'semaine']);
        Route::post('/rdv',               [SecretaireRdvController::class, 'store']);
        Route::put('/rdv/{id}',           [SecretaireRdvController::class, 'update']);
        Route::patch('/rdv/{id}/statut',  [SecretaireRdvController::class, 'updateStatut']);
        Route::delete('/rdv/{id}',        [SecretaireRdvController::class, 'destroy']);

        // Horaires & congés
        Route::get('/horaires',           [SecretaireHoraireController::class, 'index']);
        Route::post('/horaires',          [SecretaireHoraireController::class, 'store']);
        Route::delete('/horaires/{id}',   [SecretaireHoraireController::class, 'destroy']);
        Route::get('/conges',             [SecretaireHoraireController::class, 'indexConges']);
        Route::post('/conges',            [SecretaireHoraireController::class, 'storeConge']);
        Route::delete('/conges/{id}',     [SecretaireHoraireController::class, 'destroyConge']);

        // Factures & paiements
        Route::get('/factures',           [SecretaireFactureController::class, 'index']);
        Route::post('/factures',          [SecretaireFactureController::class, 'store']);
        Route::get('/factures/impayes',   [SecretaireFactureController::class, 'impayes']);
        Route::get('/factures/{id}',      [SecretaireFactureController::class, 'show']);
        Route::post('/factures/{id}/paiements', [SecretaireFactureController::class, 'storePaiement']);
    });

    // ── Espace Dentiste ───────────────────────────────────────────────────────
    Route::middleware('role:dentiste')->prefix('dentiste')->group(function () {

        // Planning
        Route::get('/planning',           [DentistePlanningController::class, 'index']);
        Route::get('/planning/{id}',      [DentistePlanningController::class, 'show']);

        // Dossiers médicaux
        Route::get('/dossiers',           [DentisteDossierController::class, 'index']);
        Route::get('/dossiers/{patientId}',   [DentisteDossierController::class, 'show']);
        Route::put('/dossiers/{patientId}',   [DentisteDossierController::class, 'update']);

        // Consultations (sous-ressource du dossier)
        Route::post('/dossiers/{patientId}/consultations',
            [DentisteDossierController::class, 'storeConsultation']);
        Route::put('/dossiers/{patientId}/consultations/{consultationId}',
            [DentisteDossierController::class, 'updateConsultation']);

        // Plans de traitement
        Route::get('/plans-traitement',   [DentistePlanTraitementController::class, 'index']);
        Route::post('/plans-traitement',  [DentistePlanTraitementController::class, 'store']);
        Route::get('/plans-traitement/{id}',  [DentistePlanTraitementController::class, 'show']);
        Route::put('/plans-traitement/{id}',  [DentistePlanTraitementController::class, 'update']);
        Route::patch('/plans-traitement/{planId}/seances/{seanceId}',
            [DentistePlanTraitementController::class, 'updateSeance']);

        // Ordonnances
        Route::get('/ordonnances',        [DentisteOrdonnanceController::class, 'index']);
        Route::post('/ordonnances',       [DentisteOrdonnanceController::class, 'store']);
        Route::get('/ordonnances/{id}',   [DentisteOrdonnanceController::class, 'show']);
        Route::put('/ordonnances/{id}',   [DentisteOrdonnanceController::class, 'update']);
        Route::patch('/ordonnances/{id}/archive', [DentisteOrdonnanceController::class, 'archive']);

        // Statistiques
        Route::get('/statistiques',       [DentisteStatController::class, 'index']);
    });
});