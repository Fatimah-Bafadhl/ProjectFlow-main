<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Employee;

class Task extends Model
{
    use HasFactory, \App\Models\Concerns\CascadesSoftDeletes;

    public function cascadeRelations(): array
    {
        return ['comments', 'attachments'];
    }
    protected $primaryKey = 'task_id'; 

     protected $fillable = [
        'task_title',
        'company_name',
       // 'project_id',
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
        return (\App\Enums\TaskPriority::tryFrom((string) $this->priority) ?? \App\Enums\TaskPriority::Medium)->badgeLabel();
    }

    public function getStatusLabelAttribute(): string
    {
        $status = \App\Enums\TaskStatus::tryFrom((string) $this->status);

        return $status ? $status->label() : (string) $this->status;
    }

    // كلاس التلوين للأولوية (يُستخدم مباشرة في Blade)
    public function getPriorityClassAttribute(): string
    {
        return (\App\Enums\TaskPriority::tryFrom((string) $this->priority) ?? \App\Enums\TaskPriority::Medium)->badgeClass();
    }

    public function getStatusClassAttribute(): string
    {
        return \App\Enums\TaskStatus::tryFrom((string) $this->status)?->badgeClass() ?? 'badge-status-default';
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



     public function assignedEmployees()
    {
        return $this->belongsToMany(Employee::class, 'task_employee', 'task_id', 'employee_id');
    }
}

