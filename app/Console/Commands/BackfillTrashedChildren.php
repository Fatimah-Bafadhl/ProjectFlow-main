<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTrashedChildren extends Command
{
    protected $signature = 'backfill:trashed-children {--apply : Save the changes (without this option nothing is saved)}';

    protected $description = "M2 one-off: trash the live children of already-trashed tasks and projects, with the parent's exact deleted_at.";

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? 'APPLY mode: changes will be saved.' : 'DRY RUN: nothing will be saved. Add --apply to save.');

        $total = 0;

        DB::beginTransaction();

        try {
            // 1. Tasks trashed on their own: their live comments/attachments get the task's time.
            foreach (Task::onlyTrashed()->get() as $task) {
                $count = $task->cascadeSoftDeleteTo((string) $task->getRawOriginal('deleted_at'));
                $total += $count;
                $this->report("task #{$task->task_id}", $count);
            }

            // 2. Trashed projects: stages, tasks (with their comments/attachments), documents, comments, tickets.
            foreach (Project::withArchived()->onlyTrashed()->get() as $project) {
                $stamp = (string) $project->getRawOriginal('deleted_at');

                // The old code trashed the stages a moment before the project. Give them the project's exact time.
                $stages = ProjectStage::onlyTrashed()
                    ->where('project_id', $project->project_id)
                    ->where('deleted_at', '!=', $stamp)
                    ->toBase()
                    ->update(['deleted_at' => $stamp]);

                $count = $stages + $project->cascadeSoftDeleteTo($stamp);
                $total += $count;
                $this->report("project #{$project->project_id}", $count);
            }

            if ($apply) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        $this->newLine();
        $this->info(($apply ? 'Saved. Rows changed: ' : 'Dry run finished. Rows that would change: ').$total);

        return self::SUCCESS;
    }

    private function report(string $label, int $count): void
    {
        if ($count > 0) {
            $this->line("  {$label}: {$count} row(s)");
        }
    }
}