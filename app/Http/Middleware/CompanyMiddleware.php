<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق: هل المستخدم مسجل دخول وهل هو "شركة" (Role = 1)؟
        if ($request->user() && $request->user()->role == 1) {
            return $next($request);
        }

        // إذا لم يكن شركة، نرفض الدخول ونرسل رسالة خطأ
        return response()->json([
            'message' => 'عذراً، هذا الرابط مخصص لحسابات الشركات فقط.'
        ], 403);
    }
}
