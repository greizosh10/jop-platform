<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CvAiService
{
    public function optimizeDescription($rawText)
    {
        $apiKey = env('GEMINI_API_KEY');

        // الرابط الصحيح مع علامة الاستفهام وكلمة key
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->withOptions([
                'verify' => false, // مهم جداً لتجاوز مشاكل SSL في السيرفر المحلي
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => "حول المهام التالية إلى نقاط احترافية قوية للسيرة الذاتية وباللغة العربية: " . $rawText]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // التأكد من استخراج النص بالمسار الصحيح
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return trim($result['candidates'][0]['content']['parts'][0]['text']);
                }
            }

            // إذا فشل الطلب، سجل الخطأ في اللوغ لتعرفه
            Log::error("Gemini Error Detail: " . $response->body());

            // لإظهار الخطأ في Postman مؤقتاً للتأكد، استبدل السطر التالي بـ return $response->body();
            return $rawText;

        } catch (\Exception $e) {
            Log::error("Gemini Exception: " . $e->getMessage());
            return $rawText;
        }
    }
}
