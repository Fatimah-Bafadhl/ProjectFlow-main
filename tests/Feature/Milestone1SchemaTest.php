<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Milestone1SchemaTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(): int
    {
        return DB::table('projects')->insertGetId([
            'project_name' => 'Test project',
            'project_description' => 'Test description',
            'start_project' => '2026-01-01',
            'end_project' => '2026-12-31',
        ], 'project_id');
    }

    private function makeTask(int $projectId): int
    {
        return DB::table('tasks')->insertGetId([
            'task_title' => 'Test task',
            'task_description' => 'Test description',
            'start_task' => '2026-01-01',
            'end_task' => '2026-01-31',
            'project_id' => $projectId,
        ], 'task_id');
    }

    private function makeEmployee(string $email = 'emp@example.com'): int
    {
        return DB::table('employees')->insertGetId([
            'name' => 'Test employee',
            'department' => 'IT',
            'email' => $email,
            'phone' => '0500000000',
        ], 'employee_id');
    }

    private function makeClient(string $email = 'client@example.com'): int
    {
        return DB::table('clients')->insertGetId([
            'name' => 'Test client',
            'company_name' => 'Test company',
            'email' => $email,
            'phone' => '0500000000',
        ], 'client_id');
    }

    private function assign(int $taskId, int $employeeId): void
    {
        DB::table('task_employee')->insert([
            'task_id' => $taskId,
            'employee_id' => $employeeId,
        ]);
    }

    public function test_foreign_keys_are_on_in_the_test_database(): void
    {
        $this->assertEquals(1, DB::select('PRAGMA foreign_keys')[0]->foreign_keys);
    }

    public function test_soft_delete_columns_exist(): void
    {
        foreach (['users', 'comments', 'tickets'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'deleted_at'), "{$table}.deleted_at is missing");
        }
    }

    public function test_hard_deleting_a_task_removes_its_assignments(): void
    {
        $taskId = $this->makeTask($this->makeProject());
        $employeeId = $this->makeEmployee();
        $this->assign($taskId, $employeeId);

        DB::table('tasks')->where('task_id', $taskId)->delete();

        $this->assertSame(0, DB::table('task_employee')->count());
        $this->assertSame(1, DB::table('employees')->count());
    }

    public function test_hard_deleting_an_employee_removes_assignments_but_keeps_the_task(): void
    {
        $taskId = $this->makeTask($this->makeProject());
        $employeeId = $this->makeEmployee();
        $this->assign($taskId, $employeeId);

        DB::table('employees')->where('employee_id', $employeeId)->delete();

        $this->assertSame(0, DB::table('task_employee')->count());
        $this->assertSame(1, DB::table('tasks')->count());
    }

    public function test_hard_deleting_a_project_with_assigned_tasks_works(): void
    {
        $projectId = $this->makeProject();
        $taskId = $this->makeTask($projectId);
        $this->assign($taskId, $this->makeEmployee());

        DB::table('projects')->where('project_id', $projectId)->delete();

        $this->assertSame(0, DB::table('tasks')->count());
        $this->assertSame(0, DB::table('task_employee')->count());
    }

    public function test_the_same_employee_cannot_be_assigned_to_a_task_twice(): void
    {
        $taskId = $this->makeTask($this->makeProject());
        $employeeId = $this->makeEmployee();
        $this->assign($taskId, $employeeId);

        $this->expectException(QueryException::class);
        $this->assign($taskId, $employeeId);
    }

    public function test_employee_emails_must_be_unique(): void
    {
        $this->makeEmployee('same@example.com');

        $this->expectException(QueryException::class);
        $this->makeEmployee('same@example.com');
    }

    public function test_client_emails_must_be_unique(): void
    {
        $this->makeClient('same@example.com');

        $this->expectException(QueryException::class);
        $this->makeClient('same@example.com');
    }
}