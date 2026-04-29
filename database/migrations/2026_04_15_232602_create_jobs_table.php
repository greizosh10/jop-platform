<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

   public function up(): void
{
    Schema::create('jobs', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description');
        $table->string('company_name');
        $table->string('location');
        $table->decimal('salary', 10, 2)->nullable();
        $table->timestamps();
    });
}


    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
