<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsProjectData;
use Tests\TestCase;

class Milestone5ValidationTest extends TestCase
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

    private function makeClientUser(): array
    {
        $user = User::create([
            'username' => 'cli'.uniqid(),
            'email'    => uniqid().'@example.com',
            'password' => 'password123',
            'role'     => Role::Client,
            'phone'    => '0500000000',
        ]);
        $client = Client::create([
            'user_id'      => $user->user_id,
            'name'         => $user->username,
            'company_name' => 'Acme',
            'email'        => $user->email,
            'phone'        => $user->phone,
        ]);

        return [$user, $client];
    }

    // --- Phone rule enforced server-side, every page ---

    public function test_phone_is_required_when_creating_a_user(): void
    {
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($admin)->post(route('users.store'), [
            'username' => 'New Person',
            'email'    => 'newperson@example.com',
            'password' => 'password123',
            'role'     => 'manager',
            'phone'    => '',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', ['email' => 'newperson@example.com']);
    }

    public function test_phone_must_match_the_saudi_format_when_creating_a_user(): void
    {
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($admin)->post(route('users.store'), [
            'username' => 'New Person',
            'email'    => 'newperson2@example.com',
            'password' => 'password123',
            'role'     => 'manager',
            'phone'    => '12345',
        ])->assertSessionHasErrors('phone');
    }

    public function test_phone_is_required_on_profile_update(): void
    {
        $user = $this->makeUser(Role::Manager);

        $this->actingAs($user)->put(route('profile.update'), [
            'username' => $user->username,
            'email'    => $user->email,
            'phone'    => '',
        ])->assertSessionHasErrors('phone');
    }

    public function test_phone_must_match_the_saudi_format_on_the_employees_page(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [, $employee] = $this->makeEmployeeUser();

        $this->actingAs($admin)->put(route('employees.update', $employee), [
            'name'       => $employee->name,
            'department' => $employee->department,
            'email'      => $employee->email,
            'phone'      => '0837474',
        ])->assertSessionHasErrors('phone');
    }

    // --- I7: editing from any page keeps the user row and the person row identical ---

    public function test_editing_an_employee_from_the_users_page_keeps_user_and_employee_identical(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [$user, $employee] = $this->makeEmployeeUser();

        $this->actingAs($admin)->put(route('users.update', $user), [
            'username'   => 'Updated Name',
            'email'      => 'updated@example.com',
            'phone'      => '0511111111',
            'role'       => 'employee',
            'department' => 'HR',
        ])->assertRedirect();

        $user->refresh();
        $employee->refresh();

        $this->assertSame('Updated Name', $user->username);
        $this->assertSame('Updated Name', $employee->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('updated@example.com', $employee->email);
        $this->assertSame('0511111111', $user->phone);
        $this->assertSame('0511111111', $employee->phone);
        $this->assertSame('HR', $employee->department);
    }

    public function test_editing_an_employee_from_the_employees_page_keeps_user_and_employee_identical(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [$user, $employee] = $this->makeEmployeeUser();

        $this->actingAs($admin)->put(route('employees.update', $employee), [
            'name'       => 'Employee Edited',
            'department' => 'Finance',
            'email'      => 'empedited@example.com',
            'phone'      => '0522222222',
        ])->assertRedirect();

        $user->refresh();
        $employee->refresh();

        $this->assertSame('Employee Edited', $user->username);
        $this->assertSame('Employee Edited', $employee->name);
        $this->assertSame('empedited@example.com', $user->email);
        $this->assertSame('empedited@example.com', $employee->email);
        $this->assertSame('0522222222', $user->phone);
        $this->assertSame('Finance', $employee->department);
    }

    public function test_editing_a_client_from_the_clients_page_keeps_user_and_client_identical(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [$user, $client] = $this->makeClientUser();

        $this->actingAs($admin)->put(route('clients.update', $client), [
            'name'         => 'Client Edited',
            'company_name' => 'New Co',
            'email'        => 'clientedited@example.com',
            'phone'        => '0533333333',
        ])->assertRedirect();

        $user->refresh();
        $client->refresh();

        $this->assertSame('Client Edited', $user->username);
        $this->assertSame('Client Edited', $client->name);
        $this->assertSame('clientedited@example.com', $user->email);
        $this->assertSame('clientedited@example.com', $client->email);
        $this->assertSame('0533333333', $user->phone);
        $this->assertSame('New Co', $client->company_name);
    }

    public function test_editing_own_profile_updates_the_user_row(): void
    {
        $user = $this->makeUser(Role::Manager);

        $this->actingAs($user)->put(route('profile.update'), [
            'username' => 'Profile Edited',
            'email'    => 'profileedited@example.com',
            'phone'    => '0544444444',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Profile Edited', $user->username);
        $this->assertSame('profileedited@example.com', $user->email);
        $this->assertSame('0544444444', $user->phone);
    }

    // --- Role transitions: only manager<->employee allowed, admin/client locked, self locked ---

    public function test_manager_can_be_changed_to_employee(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $manager = $this->makeUser(Role::Manager);

        $this->actingAs($admin)->put(route('users.update', $manager), [
            'username'   => $manager->username,
            'email'      => $manager->email,
            'phone'      => $manager->phone,
            'role'       => 'employee',
            'department' => 'IT',
        ])->assertRedirect();

        $this->assertSame('employee', $manager->fresh()->role->value);
        $this->assertNotNull(Employee::where('user_id', $manager->user_id)->first());
    }

    public function test_employee_can_be_changed_to_manager(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [$user, $employee] = $this->makeEmployeeUser();

        $this->actingAs($admin)->put(route('users.update', $user), [
            'username' => $user->username,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'role'     => 'manager',
        ])->assertRedirect();

        $this->assertSame('manager', $user->fresh()->role->value);
        $this->assertNotNull(Employee::onlyTrashed()->where('employee_id', $employee->employee_id)->first());
    }

    public function test_admin_role_cannot_be_changed_by_another_admin(): void
    {
        $actingAdmin = $this->makeUser(Role::Admin);
        $targetAdmin = $this->makeUser(Role::Admin);

        $this->actingAs($actingAdmin)->put(route('users.update', $targetAdmin), [
            'username' => $targetAdmin->username,
            'email'    => $targetAdmin->email,
            'phone'    => $targetAdmin->phone,
            'role'     => 'manager',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $targetAdmin->fresh()->role->value);
    }

    public function test_client_role_cannot_be_changed(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [$user] = $this->makeClientUser();

        $this->actingAs($admin)->put(route('users.update', $user), [
            'username' => $user->username,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'role'     => 'manager',
        ])->assertSessionHasErrors('role');

        $this->assertSame('client', $user->fresh()->role->value);
    }

    public function test_manager_cannot_be_changed_to_admin(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $manager = $this->makeUser(Role::Manager);

        $this->actingAs($admin)->put(route('users.update', $manager), [
            'username' => $manager->username,
            'email'    => $manager->email,
            'phone'    => $manager->phone,
            'role'     => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertSame('manager', $manager->fresh()->role->value);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $this->makeUser(Role::Admin); // second admin, so the last-admin guard isn't the reason for the block

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'username' => $admin->username,
            'email'    => $admin->email,
            'phone'    => $admin->phone,
            'role'     => 'manager',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role->value);
    }

    public function test_a_manager_cannot_reach_the_users_update_route_at_all(): void
    {
        // users.update is admin-only middleware — a manager can't reach it even to try
        // changing their own role. The self-lock rule itself is proven by the admin
        // self-change test above, which DOES reach the route.
        $manager = $this->makeUser(Role::Manager);

        $this->actingAs($manager)->put(route('users.update', $manager), [
            'username' => $manager->username,
            'email'    => $manager->email,
            'phone'    => $manager->phone,
            'role'     => 'employee',
        ])->assertForbidden();

        $this->assertSame('manager', $manager->fresh()->role->value);
    }
    // --- Creating without a required role-specific field leaves no orphan user (A3) ---

    public function test_creating_an_employee_without_a_department_leaves_no_orphan_user(): void
    {
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($admin)->post(route('users.store'), [
            'username' => 'No Dept',
            'email'    => 'nodept@example.com',
            'password' => 'password123',
            'role'     => 'employee',
            'phone'    => '0555555555',
        ])->assertSessionHasErrors('department');

        $this->assertDatabaseMissing('users', ['email' => 'nodept@example.com']);
    }

    public function test_creating_a_client_without_a_company_name_leaves_no_orphan_user(): void
    {
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($admin)->post(route('users.store'), [
            'username' => 'No Company',
            'email'    => 'nocompany@example.com',
            'password' => 'password123',
            'role'     => 'client',
            'phone'    => '0566666666',
        ])->assertSessionHasErrors('company_name');

        $this->assertDatabaseMissing('users', ['email' => 'nocompany@example.com']);
    }

    // --- Email uniqueness across users/employees/clients, including trashed (A4, A10, decision 9) ---

    public function test_duplicate_email_shows_a_visible_error_on_the_employees_page(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [, $existingEmployee] = $this->makeEmployeeUser();
        [, $targetEmployee] = $this->makeEmployeeUser();

        $this->actingAs($admin)->put(route('employees.update', $targetEmployee), [
            'name'       => $targetEmployee->name,
            'department' => $targetEmployee->department,
            'email'      => $existingEmployee->email,
            'phone'      => $targetEmployee->phone,
        ])->assertSessionHasErrors('email');
    }

    public function test_duplicate_email_shows_a_visible_error_on_the_clients_page(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [, $existingClient] = $this->makeClientUser();
        [, $targetClient] = $this->makeClientUser();

        $this->actingAs($admin)->put(route('clients.update', $targetClient), [
            'name'         => $targetClient->name,
            'company_name' => $targetClient->company_name,
            'email'        => $existingClient->email,
            'phone'        => $targetClient->phone,
        ])->assertSessionHasErrors('email');
    }

    public function test_email_belonging_to_a_trashed_person_gives_a_friendly_message(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [$trashedUser] = $this->makeEmployeeUser();
        $trashedUser->delete();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'username' => 'Reuse Email',
            'email'    => $trashedUser->email,
            'password' => 'password123',
            'role'     => 'manager',
            'phone'    => '0577777777',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('المحذوفات', session('errors')->first('email'));
    }

    // --- project_ids must point at non-trashed projects only ---

    public function test_client_project_ids_reject_a_trashed_project(): void
    {
        $admin = $this->makeUser(Role::Admin);
        [, $client] = $this->makeClientUser();
        $trashedProject = $this->makeProject('Trashed');
        $trashedProject->delete();

        $this->actingAs($admin)->put(route('clients.update', $client), [
            'name'         => $client->name,
            'company_name' => $client->company_name,
            'email'        => $client->email,
            'phone'        => $client->phone,
            'project_ids'  => [$trashedProject->project_id],
        ])->assertSessionHasErrors('project_ids.0');
    }

    // --- N+1 fix on the Users page ---

   public function test_users_index_only_queries_the_employees_table_once(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $this->makeEmployeeUser();
        $this->makeEmployeeUser();
        $this->makeEmployeeUser();
        $this->makeEmployeeUser();
        $this->makeEmployeeUser();

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $employeeQueries = collect(DB::getQueryLog())
            ->filter(fn ($entry) => str_contains($entry['query'], 'employees'))
            ->count();
        DB::disableQueryLog();

        $this->assertSame(
            1,
            $employeeQueries,
            'Expected exactly one query against the employees table (a single eager-loaded WHERE IN), not one per row.'
        );
    }
}