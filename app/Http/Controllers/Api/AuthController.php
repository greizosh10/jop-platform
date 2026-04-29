<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // 1. تسجيل المستخدم العادي (باحث عن عمل)
    public function registerUser(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:users',
            'password'          => 'required|min:6',
            'phone'             => ['required', 'string', 'regex:/^09[345689]\d{7}$/'],
            'address'           => 'required|string',
            'skills'            => 'required|string',
            'telegram_username' => 'required|string|max:50',
            'profile_image'     => 'nullable|file|max:5120',
        ]);

        $data['role'] = 0; // تحديد الدور تلقائياً كمستخدم

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $this->uploadImage($request->file('profile_image'));
        }

        $user = $this->userService->handleRegister($data);
        return response()->json($user, 201);
    }

    // 2. تسجيل الشركة (صاحب عمل)
    public function registerCompany(Request $request)
    {
        $data = $request->validate([

            'company_name'      => 'required|string|unique:users,company_name',
            'email'             => 'required|email|unique:users',
            'password'          => 'required|min:6',
            'phone'             => ['required', 'string', 'regex:/^09[345689]\d{7}$/'],
            'address'           => 'required|string', // مقر الشركة الرئيسي
            'tahass'=>'required|string',
            'website_url'       => 'required|url',
            'telegram_username' => 'nullable|string|max:50', // أضفناه هنا كحقل اختياري للشركة
            'profile_image'     => 'nullable|file|max:5120',
        ]);

        $data['role'] = 1;
        $data['name'] = $data['company_name'];

        // إذا لم تقم الشركة بإدخال تيليجرام، نضع قيمة افتراضية
        $data['telegram_username'] = $data['telegram_username'] ?? 'N/A';

        // الشركات عادة لا تملك مهارات فردية مثل المستخدمين
        $data['skills'] = 'Company Profile';

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $this->uploadImage($request->file('profile_image'));
        }

        $user = $this->userService->handleRegister($data);
        return response()->json($user, 201);
    }

    // دالة مساعدة لرفع الصور (منعاً لتكرار الكود)
    private function uploadImage($file)
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = Str::random(40) . '.' . $extension;
        return $file->storeAs('profiles', $fileName, 'public');
    }
public function login(Request $request)
    {
        // 1. التحقق من مدخلات المستخدم
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // 2. محاولة تسجيل الدخول
        if (!auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'بيانات الاعتماد غير صحيحة، يرجى التأكد من البريد الإلكتروني وكلمة المرور.'
            ], 401);
        }

        // 3. الحصول على بيانات المستخدم بعد النجاح
        $user = auth()->user();

        // 4. توليد توكن جديد (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. الرد ببيانات المستخدم مع التوكن
        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user'    => $user,
            'token'   => $token,
            'role'    => $user->role // نرسل الدور (0 أو 1) ليسهل على الفرونت إند توجيه المستخدم
        ], 200);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }

}
