php artisan migrate<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id('employee_id');
            $table->string('name');         
            $table->string('department'); // تم استبدال اسم الشركة بالقسم
            $table->string('email')->unique();        
            $table->string('phone');        
            
            // ربط الموظف بالمستخدم (الأدمن) الذي قام بإضافته
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};