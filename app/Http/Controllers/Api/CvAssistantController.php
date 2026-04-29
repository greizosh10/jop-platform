<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CvDataService;

class CvAssistantController extends Controller
{
    protected $cvDataService;

    public function __construct(CvDataService $cvDataService)
    {
        $this->cvDataService = $cvDataService;
    }

    /**
     * إضافة خبرة عملية جديدة وتحسينها بالذكاء الاصطناعي
     */
    public function addExperience(Request $request)
    {
        // 1. التحقق من البيانات القادمة
        $validatedData = $request->validate([
            'job_title'       => 'required|string|max:255',
            'company_name'    => 'required|string|max:255',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date',
            'raw_description' => 'required|string|min:10', // النص البسيط الذي يكتبه المستخدم
        ]);

        try {
            // 2. استدعاء الخدمة لحفظ البيانات وتحسينها ذكياً
            $experience = $this->cvDataService->storeExperience($validatedData);

            return response()->json([
                'status'  => 'success',
                'message' => 'تم تحسين النص وحفظ الخبرة بنجاح!',
                'data'    => $experience
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء الاتصال بالذكاء الاصطناعي: ' . $e->getMessage()
            ], 500);
        }
    }
}
