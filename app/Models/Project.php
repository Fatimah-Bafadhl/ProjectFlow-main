<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ProjectType;

class Project extends Model
{
    use HasFactory,SoftDeletes;

    protected $primaryKey = 'project_id';

    protected $fillable = [
        'project_name', 
        'company_name', 
        'project_description', 
        'start_project', 
        'end_project', 
        'status', 
        'progress', 
        'user_id',
        'creator_name',
        'project_type'

    ];

    // دالة مزامنة وتحديث حالة ونسبة المشروع تلقائياً بناءً على مهامه

        protected function casts(): array
    {
        return [
            'project_type' => ProjectType::class,
        ];
    }

    public function syncStatus()
{
    $tasks = $this->tasks;
    $totalTasks = $tasks->count();

    if ($totalTasks === 0) {
        $this->progress = 0;
        $this->status = 'قيد الانتظار';
        $this->save();
        return;
    }

    // 1. حساب النسبة المئوية بشكل تدريجي بناءً على وزن كل حالة مهمة
    $totalWeight = 0;
    foreach ($tasks as $task) {
        $status = trim($task->status);
        switch ($status) {
            case 'مكتمل':
            case 'مكتملة':
                $totalWeight += 100;
                break;
            case 'قيد المراجعة':
                $totalWeight += 75;
                break;
            case 'قيد التنفيذ':
                $totalWeight += 50;
                break;
            case 'متوقف مؤقتاً':
            case 'متوقف مؤقتا':
                $totalWeight += 25;
                break;
            default:
                $totalWeight += 0;
                break;
        }
    }

    $this->progress = round($totalWeight / $totalTasks);

    // 2. ضبط حالة المشروع تلقائياً بناءً على أولويات المهام الحالية
    $allCompleted = $tasks->every(fn($t) => in_array(trim($t->status), ['مكتمل', 'مكتملة']));
    $hasInReview = $tasks->contains(fn($t) => trim($t->status) === 'قيد المراجعة');
    $hasInProgress = $tasks->contains(fn($t) => trim($t->status) === 'قيد التنفيذ');
    $hasOnHold = $tasks->contains(fn($t) => trim($t->status) === 'متوقف مؤقتاً' || trim($t->status) === 'متوقف مؤقتا');

    if ($allCompleted) {
        $this->status = 'مكتملة';
    } elseif ($hasInReview) {
        $this->status = 'قيد المراجعة';
    } elseif ($hasInProgress) {
        $this->status = 'قيد التنفيذ';
    } elseif ($hasOnHold) {
        $this->status = 'متوقف مؤقتاً';
    } else {
        $this->status = 'قيد الانتظار';
    }

    $this->save();
}
    // خاصية محسوبة لضمان قراءة النسبة بشكل صحيح
    public function getProgressAttribute($value)
    {
        if (!is_null($value) && $value > 0) {
            return $value;
        }

        $tasks = $this->tasks;
        if ($tasks->count() > 0) {
            return round($tasks->avg(function ($task) {
                switch ($task->status) {
                    case 'مكتملة': return 100;
                    case 'قيد التنفيذ': return 50;
                    case 'قيد الانتظار': return 0;
                    default: return 0;
                }
            }));
        }

        return $value ?? 0;
    }

        public function archive()
    {
        $this->archived_at = now();
        $this->save();
    }

    public function unarchive()
    {
        $this->archived_at = null;
        $this->save();
    }

    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

        protected static function booted()
    {
        static::addGlobalScope('notArchived', function ($query) {
            $query->whereNull('archived_at');
        });
    }

    public function scopeWithArchived($query)
    {
        return $query->withoutGlobalScope('notArchived');
    }

    public function scopeOnlyArchived($query)
    {
        return $query->withoutGlobalScope('notArchived')->whereNotNull('archived_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id', 'project_id');
    }

    public function stages()
    {
        return $this->hasMany(ProjectStage::class, 'project_id', 'project_id')->orderBy('stage_order');
    }

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function managers()
{
    return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id');
}

public function employees()
{
    return $this->belongsToMany(Employee::class, 'project_employee', 'project_id', 'employee_id');
}

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_project', 'project_id', 'client_id');
    }

    public function comments()
     { return $this->hasMany(Comment::class, 'project_id', 'project_id'); }

     public function tickets()
      { return $this->hasMany(Ticket::class, 'project_id', 'project_id'); }
}