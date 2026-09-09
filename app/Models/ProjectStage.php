<?php

namespace App\Models;

use App\Enums\ProjectStageStatus;
use App\Enums\ProjectStageName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStage extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'project_stage_id';

    protected $fillable = [
        'project_id',
        'stage_key',
        'stage_order',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStageStatus::class,
            'stage_key' => ProjectStageName::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    
       public function tasks()
    {
        return $this->hasMany(Task::class, 'stage_id', 'project_stage_id');
    }

    public function taskProgressPercent(): int
    {
        $stageTasks = $this->tasks;
        $taskCount = $stageTasks->count();

        if ($taskCount === 0) {
            return 0;
        }

        $totalWeight = 0;
        foreach ($stageTasks as $task) {
            $status = trim($task->status);
            $totalWeight += match (true) {
                in_array($status, ['مكتمل', 'مكتملة']) => 100,
                $status === 'قيد المراجعة' => 75,
                $status === 'قيد التنفيذ' => 50,
                in_array($status, ['متوقف مؤقتاً', 'متوقف مؤقتا']) => 25,
                default => 0,
            };
        }

        return (int) round($totalWeight / $taskCount);
    }
}