<?php
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Auth;
use App\Models\CvSummary;
use App\Models\Experience;
use Barryvdh\DomPDF\Facade\Pdf;
use ArPHP\I18N\Arabic;

// مسار وهمي لتجنب خطأ لارافيل عند عدم تسجيل الدخول
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated. Please login via API.'], 401);
})->name('login');

// مسار عرض السيرة الذاتية - سحب بيانات المستخدم الحقيقي
Route::middleware('auth:sanctum')->get('/my-cv', function () {
    $user = Auth::user(); // جلب المستخدم الذي سجل دخوله بالتوكن

    // سحب البيانات الحقيقية من قاعدة البيانات
    $summary = $user->cvSummary;
    $experiences = $user->experiences()->orderBy('start_date', 'desc')->get();

    return view('cv_display', compact('user', 'summary', 'experiences'));
});

// مسار تحميل الـ PDF الاحترافي
Route::middleware('auth:sanctum')->get('/download-cv', function () {
    $user = Auth::user();
    $summary = $user->cvSummary;
    $experiences = $user->experiences()->orderBy('start_date', 'desc')->get();

    $arabic = new Arabic();

    // معالجة النصوص للـ PDF لمنع ظهور علامات الاستفهام
    $processedUser = (object) [
        'name' => $arabic->utf8Glyphs($user->name),
        'email' => $user->email,
        'phone' => $user->phone,
        'address' => $arabic->utf8Glyphs($user->address ?? 'سوريا، دمشق'),
        'job_title' => $arabic->utf8Glyphs('مهندس تقنية معلومات')
    ];

    $summaryContent = $summary ? $arabic->utf8Glyphs($summary->content) : '';

    foreach ($experiences as $exp) {
        $exp->job_title = $arabic->utf8Glyphs($exp->job_title);
        $exp->company_name = $arabic->utf8Glyphs($exp->company_name);
        $exp->ai_optimized_description = $arabic->utf8Glyphs($exp->ai_optimized_description);
    }

    $pdf = Pdf::loadView('cv_display', [
        'user' => $processedUser,
        'summaryContent' => $summaryContent,
        'experiences' => $experiences,
        'isPdf' => true
    ]);

    return $pdf->download('CV_' . $user->name . '.pdf');
});
