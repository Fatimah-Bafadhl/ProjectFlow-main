<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsProjectData;
use Tests\TestCase;

class Milestone4PermissionsTest extends TestCase
{
    use RefreshDatabase, BuildsProjectData;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // --- D5: sync() must not drop links to trashed related rows ---

    public function test_editing_a_project_keeps_a_trashed_manager_linked(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $manager = $this->makeUser(Role::Manager);
        $project = $this->makeProject();
        $project->managers()->attach($manager->user_id);
        $manager->delete();

        $this->actingAs($admin)->put(route('projects.update', $project->project_id), [
            'project_name'        => $project->project_name,
            'company_name'        => $project->company_name,
            'project_description' => $project->project_description,
            'start_project'       => $project->start_project,
            'end_project'         => $project->end_project,
            'project_type'        => 'app',
            'manager_ids'         => [],
        ])->assertRedirect();

        $this->assertSame(1, DB::table('project_user')
            ->where('project_id', $project->project_id)
            ->where('user_id', $manager->user_id)
            ->count());
    }

    public function test_editing_a_project_keeps_a_trashed_employee_linked(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $project = $this->makeProject();
        $employee = $this->makeEmployee();
        $project->employees()->attach($employee->employee_id);
        $employee->delete();

        $this->actingAs($admin)->put(route('projects.update', $project->project_id), [
            'project_name'        => $project->project_name,
            'company_name'        => $project->company_name,
            'project_description' => $project->project_description,
            'start_project'       => $project->start_project,
            'end_project'         => $project->end_project,
            'project_type'        => 'app',
            'employee_ids'        => [],
        ])->assertRedirect();

        $this->assertSame(1, DB::table('project_employee')
            ->where('project_id', $project->project_id)
            ->where('employee_id', $employee->employee_id)
            ->count());
    }

    public function test_editing_a_project_keeps_a_trashed_client_linked(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $project = $this->makeProject();
        $client = Client::create([
            'name'         => 'Client Z',
            'company_name' => 'Co',
            'email'        => uniqid().'@example.com',
            'phone'        => '0500000000',
        ]);
        $project->clients()->attach($client->client_id);
        $client->delete();

        $this->actingAs($admin)->put(route('projects.update', $project->project_id), [
            'project_name'        => $project->project_name,
            'company_name'        => $project->company_name,
            'project_description' => $project->project_description,
            'start_project'       => $project->start_project,
            'end_project'         => $project->end_project,
            'project_type'        => 'app',
            'client_ids'          => [],
        ])->assertRedirect();

        $this->assertSame(1, DB::table('client_project')
            ->where('project_id', $project->project_id)
            ->where('client_id', $client->client_id)
            ->count());
    }

    public function test_editing_a_project_still_drops_a_live_removed_employee(): void
    {
        // Negative-style check: confirms the trashed-link fix did not break the existing,
        // intentional behavior of detaching a *live* employee that was unchecked in the form.
        $admin = $this->makeUser(Role::Admin);
        $project = $this->makeProject();
        $employee = $this->makeEmployee();
        $task = $this->makeTask($project, $employee);
        $project->employees()->attach($employee->employee_id);

        $this->actingAs($admin)->put(route('projects.update', $project->project_id), [
            'project_name'        => $project->project_name,
            'company_name'        => $project->company_name,
            'project_description' => $project->project_description,
            'start_project'       => $project->start_project,
            'end_project'         => $project->end_project,
            'project_type'        => 'app',
            'employee_ids'        => [],
        ])->assertRedirect();

        $this->assertSame(0, DB::table('project_employee')
            ->where('project_id', $project->project_id)
            ->where('employee_id', $employee->employee_id)
            ->count());
        $this->assertSame(0, DB::table('task_employee')
            ->where('task_id', $task->task_id)
            ->where('employee_id', $employee->employee_id)
            ->count());
    }

    public function test_editing_a_task_keeps_a_trashed_assigned_employee_linked(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $project = $this->makeProject();
        $employee = $this->makeEmployee();
        $task = $this->makeTask($project, $employee);
        $employee->delete();
        $newEmployee = $this->makeEmployee();

        $this->actingAs($admin)->put(route('tasks.update', $task->task_id), [
            'task_title'       => $task->task_title,
            'task_description' => $task->task_description,
            'priority'         => $task->priority,
            'status'           => $task->status,
            'start_task'       => $task->start_task,
            'end_task'         => $task->end_task,
            'assigned_to'      => [$newEmployee->employee_id],
        ])->assertRedirect();

        $this->assertSame(2, DB::table('task_employee')->where('task_id', $task->task_id)->count());
        $this->assertSame(1, DB::table('task_employee')
            ->where('task_id', $task->task_id)
            ->where('employee_id', $employee->employee_id)
            ->count());
    }

    public function test_editing_a_client_keeps_a_trashed_project_linked(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $client = Client::create([
            'name'         => 'Client Y',
            'company_name' => 'Co',
            'email'        => uniqid().'@example.com',
            'phone'        => '0500000000',
        ]);
        $trashedProject = $this->makeProject('Trashed project');
        $client->projects()->attach($trashedProject->project_id);
        $trashedProject->delete();

        $this->actingAs($admin)->put(route('clients.update', $client->client_id), [
            'name'         => $client->name,
            'company_name' => $client->company_name,
            'email'        => $client->email,
            'phone'        => $client->phone,
            'project_ids'  => [],
        ])->assertRedirect();

        $this->assertSame(1, DB::table('client_project')
            ->where('client_id', $client->client_id)
            ->where('project_id', $trashedProject->project_id)
            ->count());
    }

    // --- D7 / D9: comment delete permissions ---

    public function test_admin_can_delete_a_comment_with_no_author(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $project = $this->makeProject();
        $task = $this->makeTask($project);
        $comment = $this->makeComment($task, null);

        $this->actingAs($admin)->delete(route('comments.destroy', $comment->comment_id))
            ->assertRedirect();

        $this->assertNull(Comment::find($comment->comment_id));
    }

    public function test_a_manager_can_delete_a_comment_on_their_own_project(): void
    {
        $manager = $this->makeUser(Role::Manager);
        $project = $this->makeProject();
        $project->managers()->attach($manager->user_id);
        $task = $this->makeTask($project);
        $comment = $this->makeComment($task, null);

        $this->actingAs($manager)->delete(route('comments.destroy', $comment->comment_id))
            ->assertRedirect();

        $this->assertNull(Comment::find($comment->comment_id));
    }

    public function test_a_manager_cannot_delete_a_comment_on_another_projects_task(): void
    {
        $manager = $this->makeUser(Role::Manager);
        $otherProject = $this->makeProject();
        $task = $this->makeTask($otherProject);
        $comment = $this->makeComment($task, null);

        $this->actingAs($manager)->delete(route('comments.destroy', $comment->comment_id))
            ->assertForbidden();

        $this->assertNotNull(Comment::find($comment->comment_id));
    }

    // --- D9: document store/delete scoped to the manager's own project ---

    public function test_a_manager_can_store_and_delete_a_document_on_their_own_project(): void
    {
        $manager = $this->makeUser(Role::Manager);
        $project = $this->makeProject();
        $project->managers()->attach($manager->user_id);

        $file = UploadedFile::fake()->create('doc.pdf', 10);

        $this->actingAs($manager)->post(route('documents.store', $project->project_id), [
            'type'  => 'file',
            'title' => 'Spec',
            'file'  => $file,
        ])->assertRedirect();

        $document = ProjectDocument::where('project_id', $project->project_id)->firstOrFail();

        $this->actingAs($manager)->delete(route('documents.destroy', $document->project_document_id))
            ->assertRedirect();

        $this->assertNull(ProjectDocument::find($document->project_document_id));
    }

    public function test_a_manager_cannot_store_a_document_on_another_project(): void
    {
        $manager = $this->makeUser(Role::Manager);
        $otherProject = $this->makeProject();

        $file = UploadedFile::fake()->create('doc.pdf', 10);

        $this->actingAs($manager)->post(route('documents.store', $otherProject->project_id), [
            'type'  => 'file',
            'title' => 'Spec',
            'file'  => $file,
        ])->assertForbidden();

        $this->assertSame(0, ProjectDocument::where('project_id', $otherProject->project_id)->count());
    }

    public function test_a_manager_cannot_delete_a_document_on_another_project(): void
    {
        $manager = $this->makeUser(Role::Manager);
        $otherProject = $this->makeProject();
        $document = $this->makeDocument($otherProject, $this->fakeFile('project_documents'));

        $this->actingAs($manager)->delete(route('documents.destroy', $document->project_document_id))
            ->assertForbidden();

        $this->assertNotNull(ProjectDocument::find($document->project_document_id));
    }

    // --- D9: task attachment store scoped to manager's project / employee's own task ---

    public function test_an_employee_can_add_an_attachment_to_their_assigned_task(): void
    {
        $employeeUser = $this->makeUser(Role::Employee);
        $employee = $this->makeEmployee();
        $employee->update(['user_id' => $employeeUser->user_id]);
        $project = $this->makeProject();
        $task = $this->makeTask($project, $employee);

        $file = UploadedFile::fake()->create('a.txt', 5);

        $this->actingAs($employeeUser)->post(route('task_attachments.store', $task->task_id), [
            'type'  => 'file',
            'title' => 'Note',
            'file'  => $file,
        ])->assertRedirect();

        $this->assertSame(1, TaskAttachment::where('task_id', $task->task_id)->count());
    }

    public function test_an_employee_cannot_add_an_attachment_to_a_task_they_are_not_assigned_to(): void
    {
        $employeeUser = $this->makeUser(Role::Employee);
        $ownEmployee = $this->makeEmployee();
        $ownEmployee->update(['user_id' => $employeeUser->user_id]);

        $project = $this->makeProject();
        $otherEmployee = $this->makeEmployee();
        $task = $this->makeTask($project, $otherEmployee);

        $file = UploadedFile::fake()->create('a.txt', 5);

        $this->actingAs($employeeUser)->post(route('task_attachments.store', $task->task_id), [
            'type'  => 'file',
            'title' => 'Note',
            'file'  => $file,
        ])->assertForbidden();

        $this->assertSame(0, TaskAttachment::where('task_id', $task->task_id)->count());
    }

    public function test_a_manager_cannot_delete_an_attachment_on_another_projects_task(): void
    {
        $manager = $this->makeUser(Role::Manager);
        $otherProject = $this->makeProject();
        $task = $this->makeTask($otherProject);
        $attachment = $this->makeAttachment($task, $this->fakeFile('task_attachments'));

        $this->actingAs($manager)->delete(route('task_attachments.destroy', $attachment->task_attachment_id))
            ->assertForbidden();

        $this->assertNotNull(TaskAttachment::find($attachment->task_attachment_id));
    }

    // --- archive removal / reassign-creator removal ---

    public function test_project_pages_load_without_archived_at_column(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $project = $this->makeProject();

        $this->actingAs($admin)->get(route('projects.index'))->assertOk();
        $this->actingAs($admin)->get(route('projects.show', $project->project_id))->assertOk();
    }

    public function test_reassign_creator_route_no_longer_exists(): void
    {
        $this->assertFalse(Route::has('projects.reassignCreator'));
    }
}