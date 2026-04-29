<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\JobService;

class JobController extends Controller
{
    protected $service;

    public function __construct(JobService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->handleListingJobs());
    }

    public function store(Request $request)
    {
        // التحقق من البيانات
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'company_name' => 'required|string',
            'location' => 'required|string',
            'salary' => 'nullable|numeric',
        ]);

        // ملاحظة: تأكد أن اسم المتغير هو $this->service كما عرفته فوق
        $job = $this->service->createJob($validated);

        return response()->json([
            'message' => 'تمت إضافة الوظيفة بنجاح!',
            'job' => $job
        ], 201);
    }
}
