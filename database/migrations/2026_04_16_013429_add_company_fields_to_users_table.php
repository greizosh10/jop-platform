<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. حقل الدور (0 للمستخدم، 1 للشركة)
            if (!Schema::hasColumn('users', 'role')) {
                $table->integer('role')->default(0)->after('password');
            }

            // 2. الحقول الشخصية الأساسية (مشتركة أو للمستخدم)
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('telegram_username');
            }
            if (!Schema::hasColumn('users', 'skills')) {
                $table->text('skills')->nullable()->after('address');
            }

            // 3. حقل التخصص (الذي أضفته مؤخراً)
            if (!Schema::hasColumn('users', 'tahass')) {
                $table->string('tahass')->nullable()->after('skills');
            }

            // 4. حقول بيانات الشركات فقط
            if (!Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable()->unique()->after('tahass');
            }
            if (!Schema::hasColumn('users', 'website_url')) {
                $table->string('website_url')->nullable()->after('company_name');
            }

            // 5. حقل الصورة (اللوغو أو الصورة الشخصية)
            if (!Schema::hasColumn('users', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('website_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'phone',
                'telegram_username',
                'address',
                'skills',
                'tahass',
                'company_name',
                'website_url',
                'profile_image'
            ]);
        });
    }
};
