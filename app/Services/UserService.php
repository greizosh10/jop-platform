<?php
namespace App\Services;

use App\Repositories\UserRepository;

class UserService {
    protected $repo;

    public function __construct(UserRepository $repo) {
        $this->repo = $repo;
    }

    public function handleRegister(array $data) {
        // نرسل المصفوفة كاملة للمستودع (Repository)
        $user = $this->repo->register($data);

        // إصدار توكن خاص بالمستخدم عند التسجيل باستخدام Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }
}
