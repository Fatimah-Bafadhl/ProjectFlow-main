<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;

class Task extends Model
{
    use HasFactory;

    protected $primaryKey = 'task_id'; 

    protected $fillable = [
        'task_title',
        'company_name',
        'project_id',    
        'assigned_to',
        'task_description',
        'start_task',
        'end_task',
        'status',
    ];

    // مراقبة المهام لتحديث المشروع تلقائياً عند أي تعديل أو حذف
    protected static function booted()
    {
        static::saved(function ($task) {
            if ($task->project) {
                $task->project->syncStatus();
            }
        });

        static::deleted(function ($task) {
            if ($task->project) {
                $task->project->syncStatus();
            }
        });
    }

    // خريطة الحالات الذكية
    public static function getStatusMap()
    {
        return [
            'انتظار' => 0,
            'not started' => 0,
            'تنفيذ' => 50,
            'مراجعة' => 80,
            'مكتمل' => 100,
        ];
    }

    // خاصية محسوبة لنسبة إنجاز المهمة بناءً على الحالة
    public function getProgressAttribute()
    {
        // إزالة المسافات المخفية وتحويل الحروف لصغيرة لضمان دقة المطابقة
        $status = trim(str_replace("\xC2\xA0", ' ', mb_strtolower($this->status)));

        foreach (self::getStatusMap() as $keyword => $progress) {
            if (str_contains($status, mb_strtolower($keyword))) {
                return $progress;
            }
        }
        
        return 0; // القيمة الافتراضية
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id', 'task_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(Employee::class, 'assigned_to', 'employee_id');
    }
}

