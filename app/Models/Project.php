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
        $stages = $this->stages()->with('tasks')->get();

        if ($stages->isEmpty()) {
            $this->progress = 0;
            $this->status = 'قيد الانتظار';
            $this->save();
            return;
        }

        $slice = 100 / 7;
        $totalProgress = 0;
        $inProgressStages = [];
        $allDone = true;
        $firstNonDone = null;

        foreach ($stages as $stage) {
            $stageStatus = $stage->status;

            if ($stageStatus === \App\Enums\ProjectStageStatus::Done) {
                $totalProgress += $slice;
                        } elseif ($stageStatus === \App\Enums\ProjectStageStatus::InProgress) {
                $allDone = false;
                $inProgressStages[] = $stage;
                $totalProgress += ($stage->taskProgressPercent() / 100) * $slice;
            } else {
                $allDone = false;
            }

            if ($stageStatus !== \App\Enums\ProjectStageStatus::Done && $firstNonDone === null) {
                $firstNonDone = $stage;
            }
        }

        $this->progress = round($totalProgress);

        if ($allDone) {
            $this->status = 'مكتملة';
        } elseif (count($inProgressStages) > 0) {
            $this->status = $inProgressStages[0]->stage_key->label();
        } else {
            $this->status = $firstNonDone->stage_key->label();
        }

        $this->save();
    }


/**
     * يحدد المرحلة الحالية للمشروع (قراءة فقط، لا يحفظ أي تغيير)
     * نفس منطق syncStatus(): أول مرحلة "قيد التنفيذ"، وإن لم توجد فأول مرحلة غير مكتملة،
     * وإن كانت جميع المراحل مكتملة أو لا توجد مراحل يرجع null.
     */
    public function currentStageKey(): ?\App\Enums\ProjectStageName
    {
        $stages = $this->stages;

        if ($stages->isEmpty()) {
            return null;
        }

        $firstNonDone = null;

        foreach ($stages as $stage) {
            if ($stage->status === \App\Enums\ProjectStageStatus::InProgress) {
                return $stage->stage_key;
            }

            if ($stage->status !== \App\Enums\ProjectStageStatus::Done && $firstNonDone === null) {
                $firstNonDone = $stage;
            }
        }

        return $firstNonDone?->stage_key;
    }

    /**
     * لون شارة المرحلة/الحالة، للاستخدام في قائمة المشاريع وصفحة التفاصيل لاحقاً
     */
       public function stageColor(): string
    {
        if ($this->status === 'مكتملة') {
            return '#198754';
        }

        return $this->currentStageKey()?->color() ?? '#8C8C8C';
    }


    // خاصية محسوبة لضمان قراءة النسبة بشكل صحيح

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

                static::deleting(function (Project $project) {
            if ($project->isForceDeleting()) {
                $project->stages()->withTrashed()->each(fn ($stage) => $stage->forceDelete());
            } else {
                $project->stages()->each(fn ($stage) => $stage->delete());
            }
        });

        static::restoring(function (Project $project) {
            $project->stages()->onlyTrashed()->each(fn ($stage) => $stage->restore());
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

          public function documents()
    {
        return $this->hasMany(ProjectDocument::class, 'project_id', 'project_id');
    }
}