<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // حقل الدور (0 للمستخدم، 1 للشركة)
            if (!Schema::hasColumn('users', 'role')) {
                $table->integer('role')->default(0)->after('password');
            }

            // الحقول الشخصية الأساسية
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

            // حقول بيانات الشركات
            if (!Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable()->after('skills');
            }

            if (!Schema::hasColumn('users', 'local_address')) {
                $table->string('local_address')->nullable()->after('company_name');
            }

            if (!Schema::hasColumn('users', 'website_url')) {
                $table->string('website_url')->nullable()->after('local_address');
            }

            // حقل صورة الملف الشخصي
            if (!Schema::hasColumn('users', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('website_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'phone',
                'telegram_username',
                'address',
                'skills',
                'company_name',
                'local_address',
                'website_url',
                'profile_image'
            ]);
        });
    }
};
