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
    Schema::create('comments', function (Blueprint $table) {
        $table->id('comment_id');
        $table->text('comment_text');
        $table->string('attachment')->nullable();
        
        // التعديل هنا: تحديد المفاتيح المخصصة 'task_id' و 'user_id'
        $table->foreignId('task_id')->constrained('tasks', 'task_id')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
        
        $table->timestamps();
    });
}
   
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
