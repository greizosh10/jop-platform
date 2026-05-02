<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CvAiService
{
    private string $apiKey;
    private string $apiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey  = config('gemini.api_key');
        $this->apiUrl  = $this->apiUrl  = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent";
        $this->timeout = config('gemini.request_timeout', 30);
    }

    // ─────────────────────────────────────────
    //  PUBLIC METHODS (one per CV section)
    // ─────────────────────────────────────────

    /**
     * Transform raw job description → ATS bullet points
     */
    public function optimizeExperience(string $rawText, string $jobTitle, string $targetRole = ''): array
    {
        $prompt = $this->buildExperiencePrompt($rawText, $jobTitle, $targetRole);
        $response = $this->callGemini($prompt);

        if (!$response['success']) {
            return ['success' => false, 'data' => $rawText, 'error' => $response['error']];
        }

        $bullets = $this->parseBulletPoints($response['text']);

        return [
            'success'          => true,
            'original'         => $rawText,
            'optimized_bullets' => $bullets,
            'bullet_count'     => count($bullets),
        ];
    }

    /**
     * Generate ATS-optimized professional summary
     */
    public function generateSummary(
        string $targetRole,
        string $yearsExperience,
        string $keySkills,
        string $rawNotes = ''
    ): array {
        $prompt   = $this->buildSummaryPrompt($targetRole, $yearsExperience, $keySkills, $rawNotes);
        $response = $this->callGemini($prompt);

        if (!$response['success']) {
            return ['success' => false, 'data' => $rawNotes, 'error' => $response['error']];
        }

        return [
            'success'   => true,
            'summary'   => trim($response['text']),
            'word_count' => str_word_count($response['text']),
        ];
    }

    /**
     * Parse and enhance skills list with ATS keywords
     */
    public function optimizeSkills(string $rawSkills, string $targetRole): array
    {
        $prompt   = $this->buildSkillsPrompt($rawSkills, $targetRole);
        $response = $this->callGemini($prompt);

        if (!$response['success']) {
            return ['success' => false, 'data' => $rawSkills, 'error' => $response['error']];
        }

        $parsed = $this->parseJson($response['text']);

        return [
            'success'    => true,
            'structured' => $parsed,
            'raw_ai'     => $response['text'],
        ];
    }

    /**
     * Run full ATS audit and scoring
     */
    public function auditCv(array $cvSections): array
    {
        // Cache audit results for 10 minutes to avoid repeated API calls
        $cacheKey = 'cv_audit_' . md5(json_encode($cvSections));

        return Cache::remember($cacheKey, 600, function () use ($cvSections) {
            $prompt   = $this->buildAtsAuditPrompt($cvSections);
            $response = $this->callGemini($prompt);

            if (!$response['success']) {
                return ['success' => false, 'error' => $response['error']];
            }

            $parsed = $this->parseJson($response['text']);

            return [
                'success' => true,
                'audit'   => $parsed,
            ];
        });
    }

    /**
     * Tailor CV to a specific job posting
     */
    public function tailorToJob(string $cvSummary, string $jobDescription): array
    {
        $prompt   = $this->buildJobTailoringPrompt($cvSummary, $jobDescription);
        $response = $this->callGemini($prompt);

        if (!$response['success']) {
            return ['success' => false, 'error' => $response['error']];
        }

        $parsed = $this->parseJson($response['text']);

        return [
            'success'         => true,
            'tailoring_report' => $parsed,
        ];
    }

    // ─────────────────────────────────────────
    //  PROMPT BUILDERS
    // ─────────────────────────────────────────

    private function buildExperiencePrompt(string $rawText, string $jobTitle, string $targetRole): string
    {
        $targetContext = $targetRole ? "The user is targeting roles like: {$targetRole}." : '';

        return <<<PROMPT
You are a professional CV writer and ATS optimization expert.

TASK: Transform the raw job description below into 4-6 powerful ATS-optimized bullet points.

STRICT RULES:
1. Start EVERY bullet with a strong action verb (Led, Developed, Increased, Reduced, Implemented, Managed, Automated, Delivered, Achieved, Streamlined, Designed, Launched, Optimized, Mentored, Collaborated)
2. Quantify achievements wherever possible. If no numbers exist, use (~) estimates
3. Structure: [Action Verb] + [What] + [How/Tool] + [Result/Impact]
4. Use ATS keywords for: {$jobTitle}
5. Each bullet: 15-25 words
6. Output ONLY bullets, one per line, starting with "•"
7. No headers, no markdown, no explanations
8. Past tense for past roles

{$targetContext}

JOB TITLE: {$jobTitle}

RAW INPUT:
{$rawText}

OUTPUT:
PROMPT;
    }

    private function buildSummaryPrompt(
        string $targetRole,
        string $yearsExperience,
        string $keySkills,
        string $rawNotes
    ): string {
        $rawContext = $rawNotes ? "User notes: {$rawNotes}" : '';

        return <<<PROMPT
You are an expert CV writer specializing in ATS optimization.

TASK: Write a professional summary (3 sentences, 60-80 words) for a CV.

STRUCTURE:
1. [X] experienced [Role] with expertise in [Top Skills]
2. Proven track record of [achievement area] with ability to [value delivered]
3. Passionate about [field trend], committed to [professional value]

REQUIREMENTS:
- Include 5-8 ATS keywords naturally
- No "I" pronouns, no "He/She"
- Confident, professional tone
- Output ONLY the paragraph, nothing else

TARGET ROLE: {$targetRole}
EXPERIENCE: {$yearsExperience} years
KEY SKILLS: {$keySkills}
{$rawContext}

OUTPUT:
PROMPT;
    }

    private function buildSkillsPrompt(string $rawSkills, string $targetRole): string
    {
        return <<<PROMPT
You are an ATS optimization specialist.

TASK: Organize raw skills for a {$targetRole} CV into structured categories.

OUTPUT FORMAT (strict JSON only, no other text):
{
  "technical": ["skill1", "skill2"],
  "tools": ["tool1", "tool2"],
  "soft": ["skill1", "skill2"],
  "suggested_keywords": ["keyword1*", "keyword2*"]
}

Rules:
- Use exact industry terminology (React.js not ReactJS)
- Mark AI-suggested additions with *
- Add 3-5 missing but relevant keywords for this role
- No markdown fences, pure JSON only

RAW SKILLS:
{$rawSkills}
PROMPT;
    }

    private function buildAtsAuditPrompt(array $cvSections): string
    {
        $cvContent = json_encode($cvSections, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are an ATS audit expert. Analyze this CV and return ONLY valid JSON:

{
  "ats_score": 85,
  "grade": "B+",
  "breakdown": {
    "keywords": {"score": 20, "max": 25, "feedback": "..."},
    "action_verbs": {"score": 18, "max": 20, "feedback": "..."},
    "quantification": {"score": 14, "max": 20, "feedback": "..."},
    "structure": {"score": 13, "max": 15, "feedback": "..."},
    "readability": {"score": 17, "max": 20, "feedback": "..."}
  },
  "top_improvements": ["...", "...", "..."],
  "missing_keywords": ["...", "..."]
}

CV CONTENT:
{$cvContent}
PROMPT;
    }

    private function buildJobTailoringPrompt(string $cvSummary, string $jobDescription): string
    {
        return <<<PROMPT
You are a career coach specializing in CV tailoring. Return ONLY valid JSON:

{
  "match_score": 72,
  "required_keywords_found": ["keyword1"],
  "missing_keywords": ["keyword2"],
  "suggested_summary_rewrite": "...",
  "emphasis_recommendations": ["..."],
  "priority_changes": ["..."]
}

CV SUMMARY:
{$cvSummary}

JOB DESCRIPTION:
{$jobDescription}
PROMPT;
    }

    // ─────────────────────────────────────────
    //  CORE API CALLER
    // ─────────────────────────────────────────

    private function callGemini(string $prompt): array
    {
        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->withOptions(['verify' => false])
                ->timeout($this->timeout)
                ->post("{$this->apiUrl}?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.3,  // Low = more consistent, ATS-safe output
                        'maxOutputTokens' => 1024,
                        'topP'            => 0.8,
                    ],
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $text   = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($text) {
                    return ['success' => true, 'text' => trim($text)];
                }
            }
$errorBody = $response->body();

            Log::error('Gemini API failed', [
                'status' => $response->status(),
                'body'   => $errorBody,
            ]);

            // التعديل هنا: دمج حالة الخطأ مع الرسالة لتسهيل الحل
            return [
                'success' => false,
                'error' => "Gemini API Error (Status: {$response->status()}): " . ($errorBody ?: 'No response body')
            ];

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    //  PARSERS
    // ─────────────────────────────────────────

    private function parseBulletPoints(string $text): array
    {
        $lines = explode("\n", $text);
        $bullets = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Remove bullet markers and clean up
            $line = ltrim($line, '•·-*– ');
            $line = trim($line);

            if (strlen($line) > 10) {
                $bullets[] = $line;
            }
        }

        return array_values($bullets);
    }

    private function parseJson(string $text): array
    {
        // Strip markdown code fences if present
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        $parsed = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $parsed;
        }

        // Try to extract JSON from within text
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $parsed = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $parsed;
            }
        }

        Log::warning('Failed to parse AI JSON response', ['text' => $text]);
        return ['raw_response' => $text];
    }
}
