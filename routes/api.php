<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CvAssistantController;

// ── Public Routes ──────────────────────────────────────────
Route::post('/register/user',    [AuthController::class, 'registerUser']);
Route::post('/register/company', [AuthController::class, 'registerCompany']);
Route::post('/login',            [AuthController::class, 'login']);
Route::get('/jobs',              [JobController::class, 'index']);

// ── Authenticated Routes ───────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    fn(Request $r) => $r->user());

    // ── CV Builder Routes ──────────────────────────────────
    Route::prefix('cv')->group(function () {

        // Add & AI-optimize a work experience
        Route::post('/add-experience', [CvAssistantController::class, 'addExperience']);

        // Generate professional summary
        Route::post('/generate-summary', [CvAssistantController::class, 'generateSummary']);

        // Categorize & enhance skills
        Route::post('/optimize-skills', [CvAssistantController::class, 'optimizeSkills']);

        // Run full ATS audit & scoring
        Route::post('/ats-audit', [CvAssistantController::class, 'atsAudit']);

        // Tailor CV to a specific job posting
        Route::post('/tailor-to-job', [CvAssistantController::class, 'tailorToJob']);

        // Get complete ATS-ready CV profile
        Route::get('/full-profile', [CvAssistantController::class, 'fullProfile']);
    });

    // ── Company-only Routes ────────────────────────────────
    Route::middleware('is_company')->group(function () {
        Route::post('/jobs', [JobController::class, 'store']);
        Route::get('/check-company', fn(Request $r) => response()->json([
            'message' => 'Company authenticated.',
            'company' => $r->user()->company_name,
        ]));
    });

});
