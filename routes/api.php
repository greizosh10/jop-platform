<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CvAssistantController;


Route::post('/register/user', [AuthController::class, 'registerUser']);
Route::post('/register/company', [AuthController::class, 'registerCompany']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/jobs', [JobController::class, 'index']);



Route::middleware('auth:sanctum')->group(function () {


    Route::post('/logout', [AuthController::class, 'logout']);


    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--- روابط خاصة بالشركات فقط ---
    */
    Route::middleware('is_company')->group(function () {
        // إضافة وظيفة (فقط للشركات)
        Route::post('/jobs', [JobController::class, 'store']);

        // رابط التحقق من صلاحية الشركة
        Route::get('/check-company', function (Request $request) {
            return response()->json([
                'message' => 'مرحباً بك يا صاحب الشركة، هويتك موثقة بالكامل!',
                'company' => $request->user()->company_name
            ]);
        });
    });
    Route::middleware('auth:sanctum')->group(function () {
    // ... روابطك السابقة
    Route::post('/cv/optimize', [CvAssistantController::class, 'optimizeExperience']);
    Route::middleware('auth:sanctum')->group(function () {
    // ... روابطك السابقة (register, login, etc)

    // رابط السيرة الذاتية الذكية
    Route::post('/cv/add-experience', [CvAssistantController::class, 'addExperience']);
});
});
});
