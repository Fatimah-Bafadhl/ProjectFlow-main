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
    Schema::create('projects', function (Blueprint $table) {
        $table->id('project_id');
        $table->string('project_name');
        $table->text('project_description');
        $table->date('start_project');
        $table->date('end_project');
        $table->unsignedInteger('progress')->default(0);
        $table->string('status')->default('Not started'); 
        
        // التعديل هنا: تحديد المفتاح المستهدف 'user_id' في جدول 'users'
        $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
        
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */


    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
