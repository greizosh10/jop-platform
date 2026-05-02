<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CvAiService;
use App\Services\CvDataService;
use App\Models\Experience;
use App\Models\CvSummary;
use Illuminate\Support\Facades\Auth;

class CvAssistantController extends Controller
{
    public function __construct(
        protected CvAiService   $aiService,
        protected CvDataService $cvDataService,
    ) {}

    // ──────────────────────────────────────────────
    //  POST /api/cv/add-experience
    //  Save + AI-optimize a work experience entry
    // ──────────────────────────────────────────────
    public function addExperience(Request $request)
    {
        $data = $request->validate([
            'job_title'       => 'required|string|max:255',
            'company_name'    => 'required|string|max:255',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after:start_date',
            'raw_description' => 'required|string|min:10|max:2000',
            'target_role'     => 'nullable|string|max:255',
        ]);

        try {
            $experience = $this->cvDataService->storeExperience($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Experience saved and AI-optimized successfully.',
                'data'    => $experience,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'AI optimization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────
    //  POST /api/cv/generate-summary
    //  Generate ATS-optimized professional summary
    // ──────────────────────────────────────────────
    public function generateSummary(Request $request)
    {
        $data = $request->validate([
            'target_role'      => 'required|string|max:255',
            'years_experience' => 'required|string|max:10',
            'key_skills'       => 'required|string|max:500',
            'raw_notes'        => 'nullable|string|max:1000',
        ]);

        $result = $this->aiService->generateSummary(
            $data['target_role'],
            $data['years_experience'],
            $data['key_skills'],
            $data['raw_notes'] ?? ''
        );

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['error']], 500);
        }

        // Save to cv_summaries table
        $summary = CvSummary::updateOrCreate(
            ['user_id' => Auth::id()],
            ['content' => $result['summary']]
        );

        return response()->json([
            'status'  => 'success',
            'summary' => $result['summary'],
            'word_count' => $result['word_count'],
            'saved_id' => $summary->id,
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST /api/cv/optimize-skills
    //  Categorize & enhance skills with ATS keywords
    // ──────────────────────────────────────────────
    public function optimizeSkills(Request $request)
    {
        $data = $request->validate([
            'raw_skills'  => 'required|string|max:1000',
            'target_role' => 'required|string|max:255',
        ]);

        $result = $this->aiService->optimizeSkills($data['raw_skills'], $data['target_role']);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['error']], 500);
        }

        return response()->json([
            'status'     => 'success',
            'structured_skills' => $result['structured'],
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST /api/cv/ats-audit
    //  Score the full CV against ATS criteria
    // ──────────────────────────────────────────────
    public function atsAudit(Request $request)
    {
        $user = Auth::user();

        // Gather all CV sections for audit
        $cvSections = [
            'summary'     => optional(CvSummary::where('user_id', $user->id)->first())->content,
            'experiences' => Experience::where('user_id', $user->id)
                ->get(['job_title', 'ai_optimized_description'])
                ->toArray(),
            'skills'      => $user->skills,
        ];

        $result = $this->aiService->auditCv($cvSections);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['error']], 500);
        }

        return response()->json([
            'status' => 'success',
            'audit'  => $result['audit'],
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST /api/cv/tailor-to-job
    //  Match & tailor CV to a specific job posting
    // ──────────────────────────────────────────────
    public function tailorToJob(Request $request)
    {
        $data = $request->validate([
            'job_description' => 'required|string|min:50|max:5000',
        ]);

        $user    = Auth::user();
        $summary = optional(CvSummary::where('user_id', $user->id)->first())->content ?? '';

        if (empty($summary)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Please generate your CV summary first before tailoring.',
            ], 422);
        }

        $result = $this->aiService->tailorToJob($summary, $data['job_description']);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['error']], 500);
        }

        return response()->json([
            'status'          => 'success',
            'tailoring_report' => $result['tailoring_report'],
        ]);
    }

    // ──────────────────────────────────────────────
    //  GET /api/cv/full-profile
    //  Return the complete ATS-ready CV data
    // ──────────────────────────────────────────────
    public function fullProfile()
    {
        $user = Auth::user();

        return response()->json([
            'status' => 'success',
            'cv'     => [
                'personal' => [
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'phone'   => $user->phone,
                    'address' => $user->address,
                ],
                'summary'     => optional($user->cvSummary)->content,
                'experiences' => $user->experiences()->orderBy('start_date', 'desc')->get(),
                'education'   => $user->educations()->get(),
                'skills'      => $user->skills,
            ],
        ]);
    }
}
