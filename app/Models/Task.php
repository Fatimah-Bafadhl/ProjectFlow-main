<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Employee;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'task_id'; 

    protected $fillable = [
        'task_title',
        'company_name',
       // 'project_id',    
        'assigned_to',
        'task_description',
        'start_task',
        'end_task',
        'status',
        'priority',
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

    
   

// التسمية العربية للأولوية (بصيغة المؤنث لأنها تُقرأ بعد كلمة "أولوية")
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'منخفض' => 'أولوية منخفضة',
            'متوسط' => 'أولوية متوسطة',
            'عالي'  => 'أولوية عالية',
            default => 'أولوية متوسطة',
        };
    }

    // كلاس التلوين للأولوية (يُستخدم مباشرة في Blade)
    public function getPriorityClassAttribute(): string
    {
        return match ($this->priority) {
            'منخفض' => 'badge-priority-low',
            'متوسط' => 'badge-priority-medium',
            'عالي'  => 'badge-priority-high',
            default => 'badge-priority-medium',
        };
    }




   
    
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function stage()
    {
        return $this->belongsTo(ProjectStage::class, 'stage_id', 'project_stage_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id', 'task_id');
    }

      public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id', 'task_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(Employee::class, 'assigned_to', 'employee_id');
    }
}

