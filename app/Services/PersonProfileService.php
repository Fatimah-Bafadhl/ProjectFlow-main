<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The single place that writes a person's shared fields (name, email, phone,
 * company/department) to both the users row and the linked employee/client row,
 * so the two never drift apart (fixes A1 / invariant I7).
 */
class PersonProfileService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $displayName = $data['username'] ?? $data['name'];

            $user = User::create([
                'username'     => $displayName,
                'email'        => $data['email'],
                'password'     => Hash::make($data['password']),
                'role'         => $data['role'],
                'phone'        => $data['phone'],
                'company_name' => $data['company_name'] ?? null,
            ]);

            if ($data['role'] === Role::Employee->value) {
                Employee::create([
                    'user_id'    => $user->user_id,
                    'name'       => $displayName,
                    'department' => $data['department'],
                    'email'      => $data['email'],
                    'phone'      => $data['phone'],
                ]);
            } elseif ($data['role'] === Role::Client->value) {
                $projectIds = $data['project_ids'] ?? [];

                $client = Client::create([
                    'user_id'      => $user->user_id,
                    'name'         => $displayName,
                    'company_name' => $data['company_name'],
                    'email'        => $data['email'],
                    'phone'        => $data['phone'],
                ]);

                if (!empty($projectIds)) {
                    $client->projects()->attach($projectIds);
                }
            }

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $displayName = $data['username'] ?? $data['name'] ?? $user->username;

            $userUpdate = [
                'username' => $displayName,
                'email'    => $data['email'],
                'phone'    => $data['phone'],
            ];

            if (array_key_exists('company_name', $data)) {
                $userUpdate['company_name'] = $data['company_name'];
            }
            if (!empty($data['password'])) {
                $userUpdate['password'] = Hash::make($data['password']);
            }
            if (array_key_exists('role', $data)) {
                $userUpdate['role'] = $data['role'];
            }

            $user->update($userUpdate);
            $user->refresh();

            if ($user->role === Role::Employee) {
                $employee = Employee::withTrashed()->where('user_id', $user->user_id)->first();
                if ($employee) {
                    $employee->update([
                        'name'       => $displayName,
                        'email'      => $data['email'],
                        'phone'      => $data['phone'],
                        'department' => $data['department'] ?? $employee->department,
                    ]);
                }
            } elseif ($user->role === Role::Client) {
                $client = Client::withTrashed()->where('user_id', $user->user_id)->first();
                if ($client) {
                    $client->update([
                        'name'         => $displayName,
                        'email'        => $data['email'],
                        'phone'        => $data['phone'],
                        'company_name' => $data['company_name'] ?? $client->company_name,
                    ]);

                    if (array_key_exists('project_ids', $data)) {
                        $submitted = $data['project_ids'] ?? [];
                        $trashedProjectIds = $client->projects()->onlyTrashed()->pluck('projects.project_id')->toArray();
                        $client->projects()->sync(array_unique(array_merge($submitted, $trashedProjectIds)));
                    }
                }
            }

            return $user->fresh();
        });
    }
}