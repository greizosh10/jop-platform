<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('experiences', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('job_title'); // المسمى الوظيفي
        $table->string('company_name');
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->text('raw_description'); // النص اللي بكتبه المستخدم (بسيط)
        $table->text('ai_optimized_description')->nullable(); // النص اللي حيولده الـ AI (احترافي)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
