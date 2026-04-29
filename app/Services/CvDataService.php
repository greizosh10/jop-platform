<?php

namespace App\Services;

use App\Models\Experience;
use Illuminate\Support\Facades\Auth;

class CvDataService
{
    protected $aiService;

    public function __construct(CvAiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * حفظ الخبرة الوظيفية مع النص المحسن
     */
    public function storeExperience($data)
    {
        // استدعاء خدمة الذكاء الاصطناعي لتحسين الوصف
        $optimizedText = $this->aiService->optimizeDescription($data['raw_description']);

        // الحفظ في قاعدة البيانات باستخدام Model Experience
        return Experience::create([
            'user_id'                  => Auth::id(),
            'job_title'                => $data['job_title'] ?? 'N/A',
            'company_name'             => $data['company_name'] ?? 'N/A',
            'start_date'               => $data['start_date'] ?? null,
            'end_date'                 => $data['end_date'] ?? null,
            'raw_description'          => $data['raw_description'] ?? '',
            'ai_optimized_description' => $optimizedText, // هنا سيظهر النص المحسن فعلياً
        ]);
    }
}
