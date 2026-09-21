<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectStage;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsProjectData;
use Tests\TestCase;

class Milestone2CascadeTest extends TestCase
{
    use RefreshDatabase, BuildsProjectData;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function allFilePaths(array $t): array
    {
        return [
            $t['commentA']->attachment,
            $t['commentB']->attachment,
            $t['projectComment']->attachment,
            $t['attachmentA']->file_path,
            $t['attachmentB']->file_path,
            $t['document']->file_path,
        ];
    }

    public function test_soft_deleting_a_project_trashes_every_child_with_the_same_time(): void
    {
        $t = $this->buildProjectTree();

        $t['project']->delete();

        $stamp = Project::onlyTrashed()->findOrFail($t['project']->project_id)->getRawOriginal('deleted_at');

        // Nothing is left alive (I2)
        $this->assertSame(0, ProjectStage::count());
        $this->assertSame(0, Task::count());
        $this->assertSame(0, Comment::count());
        $this->assertSame(0, TaskAttachment::count());
        $this->assertSame(0, ProjectDocument::count());
        $this->assertSame(0, Ticket::count());

        // Every child got the project's exact time, including the nested ones
        $this->assertSame(7, ProjectStage::onlyTrashed()->where('deleted_at', $stamp)->count());
        $this->assertSame(2, Task::onlyTrashed()->where('deleted_at', $stamp)->count());
        $this->assertSame(3, Comment::onlyTrashed()->where('deleted_at', $stamp)->count());
        $this->assertSame(2, TaskAttachment::onlyTrashed()->where('deleted_at', $stamp)->count());
        $this->assertSame(1, ProjectDocument::onlyTrashed()->where('deleted_at', $stamp)->count());
        $this->assertSame(1, Ticket::onlyTrashed()->where('deleted_at', $stamp)->count());

        // Assignments survive (I4) and files stay on disk (I5)
        $this->assertSame(2, DB::table('task_employee')->count());
        foreach ($this->allFilePaths($t) as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_restoring_a_project_brings_back_only_the_children_trashed_with_it(): void
    {
        $t = $this->buildProjectTree();

        // Times have a one-second precision, so we move the clock between the deletes.
        $this->travel(10)->seconds();
        $t['taskB']->delete(); // trashed on its own, before the project

        $this->travel(10)->seconds();
        $t['project']->delete();

        $this->travel(10)->seconds();
        Project::onlyTrashed()->findOrFail($t['project']->project_id)->restore();

        $this->assertNotNull(Project::find($t['project']->project_id));
        $this->assertSame(7, ProjectStage::count());
        $this->assertNotNull(Task::find($t['taskA']->task_id));
        $this->assertNotNull(Comment::find($t['commentA']->comment_id));
        $this->assertNotNull(Comment::find($t['projectComment']->comment_id));
        $this->assertNotNull(TaskAttachment::find($t['attachmentA']->task_attachment_id));
        $this->assertNotNull(ProjectDocument::find($t['document']->project_document_id));
        $this->assertNotNull(Ticket::find($t['ticket']->ticket_id));

        // The task deleted separately earlier, and its children, stay deleted
        $this->assertNull(Task::find($t['taskB']->task_id));
        $this->assertNotNull(Task::onlyTrashed()->find($t['taskB']->task_id));
        $this->assertNull(Comment::find($t['commentB']->comment_id));
        $this->assertNull(TaskAttachment::find($t['attachmentB']->task_attachment_id));

        $this->assertSame(2, DB::table('task_employee')->count());
    }

    public function test_deleting_and_restoring_a_task_moves_its_comments_and_attachments_with_it(): void
    {
        $t = $this->buildProjectTree();

        $t['taskA']->delete();

        $this->assertNull(Comment::find($t['commentA']->comment_id));
        $this->assertNull(TaskAttachment::find($t['attachmentA']->task_attachment_id));
        $this->assertNotNull(Comment::find($t['commentB']->comment_id)); // other task untouched
        $this->assertNotNull(Project::find($t['project']->project_id));

        Task::onlyTrashed()->findOrFail($t['taskA']->task_id)->restore();

        $this->assertNotNull(Task::find($t['taskA']->task_id));
        $this->assertNotNull(Comment::find($t['commentA']->comment_id));
        $this->assertNotNull(TaskAttachment::find($t['attachmentA']->task_attachment_id));
        $this->assertSame(2, DB::table('task_employee')->count());
    }

    public function test_permanently_deleting_a_project_with_assignments_removes_rows_and_files_after_commit(): void
    {
        $t = $this->buildProjectTree();
        $t['taskB']->delete(); // a trashed child must be purged too, with its files
        $paths = $this->allFilePaths($t);

        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $t['project']->forceDelete();

        foreach (['projects', 'project_stages', 'tasks', 'task_employee', 'comments', 'tickets', 'project_documents', 'task_attachments'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} should be empty");
        }
        $this->assertSame(1, DB::table('employees')->count());

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_a_failed_permanent_delete_rolls_back_and_keeps_the_files(): void
    {
        $t = $this->buildProjectTree();
        $paths = $this->allFilePaths($t);

        // Fails AFTER the rows were already deleted, to prove the rollback.
        Project::deleted(function () {
            throw new \RuntimeException('boom');
        });

        try {
            $t['project']->forceDelete();
            $this->fail('The delete should have failed.');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(1, DB::table('projects')->count());
        $this->assertSame(7, DB::table('project_stages')->count());
        $this->assertSame(2, DB::table('tasks')->count());
        $this->assertSame(2, DB::table('task_employee')->count());
        $this->assertSame(3, DB::table('comments')->count());

        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_deleting_a_comment_document_or_attachment_from_the_ui_is_permanent(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $t = $this->buildProjectTree();
        $comment = $this->makeComment($t['taskA'], $admin, $this->fakeFile('comments'));

        $this->actingAs($admin)->delete(route('comments.destroy', $comment->comment_id))->assertRedirect();
        $this->assertNull(Comment::withTrashed()->find($comment->comment_id));
        Storage::disk('public')->assertMissing($comment->attachment);

        $this->actingAs($admin)->delete(route('documents.destroy', $t['document']->project_document_id))->assertRedirect();
        $this->assertNull(ProjectDocument::withTrashed()->find($t['document']->project_document_id));
        Storage::disk('public')->assertMissing($t['document']->file_path);

        $this->actingAs($admin)->delete(route('task_attachments.destroy', $t['attachmentA']->task_attachment_id))->assertRedirect();
        $this->assertNull(TaskAttachment::withTrashed()->find($t['attachmentA']->task_attachment_id));
        Storage::disk('public')->assertMissing($t['attachmentA']->file_path);
    }

    public function test_another_user_cannot_delete_a_comment_and_nothing_changes(): void
    {
        $owner = $this->makeUser(Role::Admin);
        $other = $this->makeUser(Role::Employee);
        $t = $this->buildProjectTree();
        $comment = $this->makeComment($t['taskA'], $owner, $this->fakeFile('comments'));

        $this->actingAs($other)->delete(route('comments.destroy', $comment->comment_id))->assertForbidden();

        $this->assertNotNull(Comment::find($comment->comment_id));
        Storage::disk('public')->assertExists($comment->attachment);
    }

    public function test_dashboard_and_task_list_ignore_children_of_a_trashed_project(): void
    {
        $this->withoutVite();
        $admin = $this->makeUser(Role::Admin);

        $trashed = $this->makeProject('Trashed project');
        $this->makeTask($trashed);
        $this->makeTicket($trashed);

        $live = $this->makeProject('Live project');
        $this->makeTask($live);
        $this->makeTicket($live);

        $trashed->delete();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('activeProjectsCount', 1)
            ->assertViewHas('activeTasksCount', 1)
            ->assertViewHas('openTicketsCount', 1);

        $this->actingAs($admin)->get(route('tasks.index'))
            ->assertOk()
            ->assertViewHas('tasks', fn ($tasks) => $tasks->count() === 1);
    }

    public function test_backfill_command_is_a_dry_run_by_default_and_fixes_old_data_with_apply(): void
    {
        $t = $this->buildProjectTree();
        $id = $t['project']->project_id;

        // Old-style state: the project is trashed, its stages were trashed a second earlier,
        // and everything else stayed alive.
        DB::table('project_stages')->where('project_id', $id)->update(['deleted_at' => '2026-01-01 09:59:59']);
        DB::table('projects')->where('project_id', $id)->update(['deleted_at' => '2026-01-01 10:00:00']);

        $this->artisan('backfill:trashed-children')->assertSuccessful();

        // Dry run: nothing was saved
        $this->assertSame(2, Task::count());
        $this->assertSame(3, Comment::count());
        $this->assertSame(1, Ticket::count());

        $this->artisan('backfill:trashed-children', ['--apply' => true])->assertSuccessful();

        $this->assertSame(0, Task::count());
        $this->assertSame(0, Comment::count());
        $this->assertSame(0, TaskAttachment::count());
        $this->assertSame(0, ProjectDocument::count());
        $this->assertSame(0, Ticket::count());
        $this->assertSame(7, ProjectStage::onlyTrashed()->where('deleted_at', '2026-01-01 10:00:00')->count());

        // The old project can now be restored completely
        Project::onlyTrashed()->findOrFail($id)->restore();

        $this->assertSame(7, ProjectStage::count());
        $this->assertSame(2, Task::count());
        $this->assertSame(3, Comment::count());
        $this->assertSame(2, TaskAttachment::count());
        $this->assertSame(1, ProjectDocument::count());
        $this->assertSame(1, Ticket::count());
    }
}