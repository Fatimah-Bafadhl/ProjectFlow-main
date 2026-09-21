<?php

namespace Tests\Support;

use App\Enums\ProjectStageName;
use App\Enums\ProjectStageStatus;
use App\Enums\Role;
use App\Models\Comment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Small builders for tests: users, projects with 7 stages, tasks with assignees,
 * comments, documents, attachments and tickets.
 * Call Storage::fake('public') in setUp() before using fakeFile().
 */
trait BuildsProjectData
{
    private int $buildCounter = 0;

    protected function makeUser(Role $role = Role::Admin): User
    {
        $n = ++$this->buildCounter;

        return User::create([
            'username' => "user{$n}",
            'email'    => "user{$n}@example.com",
            'password' => 'password123',
            'role'     => $role,
            'phone'    => '0500000000',
        ]);
    }

    protected function makeEmployee(): Employee
    {
        $n = ++$this->buildCounter;

        return Employee::create([
            'name'       => "Employee {$n}",
            'department' => 'IT',
            'email'      => "emp{$n}@example.com",
            'phone'      => '0500000000',
        ]);
    }

    protected function makeProject(string $name = 'Test project'): Project
    {
        $project = Project::create([
            'project_name'        => $name,
            'company_name'        => 'Test company',
            'project_description' => 'Test description',
            'start_project'       => '2026-01-01',
            'end_project'         => '2026-12-31',
        ]);

        foreach (ProjectStageName::cases() as $stageName) {
            $project->stages()->create([
                'stage_key'   => $stageName->value,
                'stage_order' => $stageName->order(),
                'status'      => $stageName === ProjectStageName::Planning
                    ? ProjectStageStatus::InProgress
                    : ProjectStageStatus::NotStarted,
            ]);
        }

        return $project;
    }

    protected function makeTask(Project $project, ?Employee $employee = null): Task
    {
        $task = new Task([
            'task_title'       => 'Test task',
            'task_description' => 'Test description',
            'status'           => 'قيد التنفيذ',
            'priority'         => 'متوسط',
            'start_task'       => '2026-01-01',
            'end_task'         => '2026-01-31',
        ]);
        $task->project_id = $project->project_id;
        $task->stage_id = $project->stages()->first()->project_stage_id;
        $task->save();

        $employee ??= $this->makeEmployee();
        $task->assignedEmployees()->attach($employee->employee_id);

        return $task;
    }

    protected function makeComment(Task|Project $parent, ?User $user = null, ?string $file = null): Comment
    {
        return Comment::create([
            'comment_text' => 'Test comment',
            'attachment'   => $file,
            'task_id'      => $parent instanceof Task ? $parent->task_id : null,
            'project_id'   => $parent instanceof Project ? $parent->project_id : null,
            'user_id'      => $user?->user_id,
            'author_name'  => $user?->username,
        ]);
    }

    protected function makeAttachment(Task $task, ?string $file = null): TaskAttachment
    {
        return TaskAttachment::create([
            'task_id'           => $task->task_id,
            'type'              => 'file',
            'title'             => 'Test attachment',
            'file_path'         => $file,
            'original_filename' => 'test.txt',
            'added_by_name'     => 'Tester',
        ]);
    }

    protected function makeDocument(Project $project, ?string $file = null): ProjectDocument
    {
        return ProjectDocument::create([
            'project_id'        => $project->project_id,
            'type'              => 'file',
            'title'             => 'Test document',
            'file_path'         => $file,
            'original_filename' => 'test.txt',
            'added_by_name'     => 'Tester',
        ]);
    }

    protected function makeTicket(Project $project): Ticket
    {
        return Ticket::create([
            'project_id'  => $project->project_id,
            'client_name' => 'Test client',
            'message'     => 'Test message',
            'status'      => 'open',
        ]);
    }

    /** Puts a real file on the faked "public" disk and returns its path. */
    protected function fakeFile(string $directory): string
    {
        $path = $directory.'/'.uniqid('f_', true).'.txt';
        Storage::disk('public')->put($path, 'test file');

        return $path;
    }

    /**
     * One project with: 7 stages, 2 tasks (both assigned), a comment and an attachment on
     * each task, a project comment, a project document and a ticket. Every file really exists.
     */
    protected function buildProjectTree(): array
    {
        $project = $this->makeProject();
        $employee = $this->makeEmployee();
        $taskA = $this->makeTask($project, $employee);
        $taskB = $this->makeTask($project, $employee);

        return [
            'project'        => $project,
            'employee'       => $employee,
            'taskA'          => $taskA,
            'taskB'          => $taskB,
            'commentA'       => $this->makeComment($taskA, null, $this->fakeFile('comments')),
            'commentB'       => $this->makeComment($taskB, null, $this->fakeFile('comments')),
            'projectComment' => $this->makeComment($project, null, $this->fakeFile('comments')),
            'attachmentA'    => $this->makeAttachment($taskA, $this->fakeFile('task_attachments')),
            'attachmentB'    => $this->makeAttachment($taskB, $this->fakeFile('task_attachments')),
            'document'       => $this->makeDocument($project, $this->fakeFile('project_documents')),
            'ticket'         => $this->makeTicket($project),
        ];
    }
}