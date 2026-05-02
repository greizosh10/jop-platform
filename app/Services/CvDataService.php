<?php

namespace App\Services;

use App\Models\Experience;
use Illuminate\Support\Facades\Auth;

class CvDataService
{
    public function __construct(protected CvAiService $aiService) {}

    /**
     * Store a work experience entry with AI-optimized description.
     */
    public function storeExperience(array $data): Experience
    {
        $aiResult = $this->aiService->optimizeExperience(
            rawText:    $data['raw_description'],
            jobTitle:   $data['job_title'],
            targetRole: $data['target_role'] ?? '',
        );

        // Join bullet points into a single stored string
        $optimizedText = $aiResult['success']
            ? implode("\n", $aiResult['optimized_bullets'])
            : $data['raw_description'];

        return Experience::create([
            'user_id'                  => Auth::id(),
            'job_title'                => $data['job_title'],
            'company_name'             => $data['company_name'],
            'start_date'               => $data['start_date'],
            'end_date'                 => $data['end_date'] ?? null,
            'raw_description'          => $data['raw_description'],
            'ai_optimized_description' => $optimizedText,
        ]);
    }
}
