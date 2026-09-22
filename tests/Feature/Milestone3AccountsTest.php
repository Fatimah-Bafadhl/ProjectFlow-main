<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\AccountLifecycle;
use App\Services\AccountLifecycleException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\BuildsProjectData;
use Tests\TestCase;

class Milestone3AccountsTest extends TestCase
{
    use RefreshDatabase, BuildsProjectData;

    private function makeEmployeeUser(): array
    {
        $user = User::create([
            'username' => 'emp'.uniqid(),
            'email'    => uniqid().'@example.com',
            'password' => 'password123',
            'role'     => Role::Employee,
            'phone'    => '0500000000',
        ]);
        $employee = Employee::create([
            'user_id'    => $user->user_id,
            'name'       => $user->username,
            'department' => 'IT',
            'email'      => $user->email,
            'phone'      => $user->phone,
        ]);

        return [$user, $employee];
    }

    public function test_deleting_from_the_users_page_or_the_employees_page_gives_the_same_result(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [$user1, $employee1] = $this->makeEmployeeUser();
        [$user2, $employee2] = $this->makeEmployeeUser();

        $this->actingAs($admin)->delete(route('users.destroy', $user1))->assertRedirect();
        $this->actingAs($admin)->delete(route('employees.destroy', $employee2))->assertRedirect();

        $this->assertSame(2, User::onlyTrashed()->whereIn('user_id', [$user1->user_id, $user2->user_id])->count());
        $this->assertSame(2, Employee::onlyTrashed()->whereIn('employee_id', [$employee1->employee_id, $employee2->employee_id])->count());
    }

    public function test_a_trashed_user_cannot_log_in(): void
    {
        $accounts = app(AccountLifecycle::class);
        $admin = $this->makeUser(Role::Admin);
        [$user] = $this->makeEmployeeUser();
        $accounts->delete($user, $admin);

        $this->post(route('loginUser'), ['email' => $user->email, 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_trashed_user_cannot_request_a_password_reset(): void
    {
        $accounts = app(AccountLifecycle::class);
        $admin = $this->makeUser(Role::Admin);
        [$user] = $this->makeEmployeeUser();
        $accounts->delete($user, $admin);

        $this->post('/forgot-password/process', ['email' => $user->email])
            ->assertSessionHasErrors('email');
    }

    public function test_restore_brings_back_the_user_and_the_person_together_with_assignments(): void
    {
        $accounts = app(AccountLifecycle::class);
        $admin = $this->makeUser(Role::Admin);
        [$user, $employee] = $this->makeEmployeeUser();
        $project = $this->makeProject();
        $task = $this->makeTask($project, $employee);

        $accounts->delete($employee, $admin);
        $this->assertSame(1, DB::table('task_employee')->count());

        $accounts->restore(User::onlyTrashed()->find($user->user_id));

        $this->assertNotNull(User::find($user->user_id));
        $this->assertNotNull(Employee::find($employee->employee_id));
        $this->assertSame(1, DB::table('task_employee')->count());
    }

        public function test_self_delete_is_blocked(): void
    {
        $onlyAdmin = $this->makeUser(Role::Admin);

        $this->actingAs($onlyAdmin)->delete(route('users.destroy', $onlyAdmin))
            ->assertSessionHasErrors('account');
        $this->assertNull(User::onlyTrashed()->find($onlyAdmin->user_id));
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $accounts = app(AccountLifecycle::class);
        $onlyAdmin = $this->makeUser(Role::Admin);
        $manager = $this->makeUser(Role::Manager);

        $this->expectException(AccountLifecycleException::class);
        $accounts->delete($onlyAdmin, $manager);
    }

    public function test_force_deleting_a_user_also_removes_its_employee_row(): void
    {
        $accounts = app(AccountLifecycle::class);
        $admin = $this->makeUser(Role::Admin);
        [$user, $employee] = $this->makeEmployeeUser();

        $accounts->delete($user, $admin);
        $accounts->forceDelete(User::onlyTrashed()->find($user->user_id));

        $this->assertSame(0, DB::table('users')->where('user_id', $user->user_id)->count());
        $this->assertSame(0, DB::table('employees')->where('employee_id', $employee->employee_id)->count());
    }

    public function test_permanent_delete_is_blocked_for_a_row_that_is_not_trashed(): void
    {
        $accounts = app(AccountLifecycle::class);
        [, $employee] = $this->makeEmployeeUser();

        $this->expectException(AccountLifecycleException::class);
        $accounts->forceDelete($employee);
    }

    public function test_trash_page_hides_an_employee_row_left_over_from_a_role_change(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [$user, $employee] = $this->makeEmployeeUser();

        // Simulates the employee -> manager switch: the employee row is trashed, the user stays active.
        $employee->delete();

        $this->actingAs($admin)->get(route('trash.index'))
            ->assertOk()
            ->assertViewHas('employees', fn ($rows) => $rows->doesntContain('employee_id', $employee->employee_id));
    }

    public function test_an_employee_cannot_delete_another_employee(): void
    {
        [$actorUser] = $this->makeEmployeeUser();
        [, $target] = $this->makeEmployeeUser();

        $this->actingAs($actorUser)->delete(route('employees.destroy', $target))->assertForbidden();
        $this->assertNull(Employee::onlyTrashed()->find($target->employee_id));
    }
}