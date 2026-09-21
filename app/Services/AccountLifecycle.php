<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The only place that deletes, restores or permanently deletes an account.
 * $subject can be the User, or the Employee / Client row of that user.
 *
 *   employee -> Employee row + User        client -> Client row + User
 *   manager / admin -> User only
 *
 * Task, project and client assignments are NOT touched, so a restore brings them back.
 * Everything runs in one transaction.
 */
class AccountLifecycle
{
    /** Soft delete. $actor is the admin who clicked (null for console commands). */
    public function delete(User|Employee|Client $subject, ?User $actor = null): void
    {
        DB::transaction(function () use ($subject, $actor) {
            $user = $subject instanceof User ? $subject : $subject->user;

            if ($user === null) {
                // Old row without a login: only the row itself is deleted.
                $subject->delete();

                return;
            }

            $this->guardDelete($user, $actor);

            $rows = $this->personRows($user);

            if (! $subject instanceof User && ! $rows->contains(fn ($row) => $row->is($subject))) {
                $rows->push($subject);
            }

            foreach ($rows as $row) {
                $row->delete();
            }

            $user->delete();
        });
    }

    /** Restore a trashed account: the user and its person row come back together. */
    public function restore(User|Employee|Client $subject): void
    {
        DB::transaction(function () use ($subject) {
            if ($subject instanceof User) {
                $user = $subject;
                $rows = $this->personRows($user, true);
            } else {
                $user = $subject->user_id ? User::withTrashed()->find($subject->user_id) : null;
                $rows = collect([$subject]);

                if ($user !== null) {
                    $expected = $subject instanceof Employee ? Role::Employee : Role::Client;

                    if ($user->role !== $expected) {
                        throw new AccountLifecycleException('لا يمكن الاستعادة لأن صلاحية الحساب تغيّرت.');
                    }
                }
            }

            if ($user !== null && $user->trashed()) {
                $user->restore();
            }

            foreach ($rows as $row) {
                if ($row->trashed()) {
                    $row->restore();
                }
            }
        });
    }

    /**
     * Permanent delete (Trash only).
     * A user takes its employee/client rows with it. A person row takes its user too, but only if that user is trashed.
     */
    public function forceDelete(User|Employee|Client $subject): void
    {
        if (! $subject->trashed()) {
            throw new AccountLifecycleException('يمكن الحذف النهائي فقط للعناصر الموجودة في المحذوفات.');
        }

        DB::transaction(function () use ($subject) {
            if ($subject instanceof User) {
                $user = $subject;
                $rows = collect()
                    ->concat(Employee::withTrashed()->where('user_id', $user->user_id)->get())
                    ->concat(Client::withTrashed()->where('user_id', $user->user_id)->get());
            } else {
                $rows = collect([$subject]);
                $user = $subject->user_id ? User::withTrashed()->find($subject->user_id) : null;

                if ($user !== null && ! $user->trashed()) {
                    $user = null; // a live login is never removed by deleting an old person row
                }
            }

            foreach ($rows as $row) {
                $row->forceDelete();
            }

            if ($user !== null) {
                $user->notifications()->delete();
                $user->forceDelete();
            }
        });
    }

    private function guardDelete(User $user, ?User $actor): void
    {
        if ($actor !== null && $actor->user_id === $user->user_id) {
            throw new AccountLifecycleException('لا يمكنك حذف حسابك الخاص.');
        }

        if ($user->role === Role::Admin && User::where('role', Role::Admin->value)->count() <= 1) {
            throw new AccountLifecycleException('لا يمكن حذف آخر مدير عام في النظام.');
        }
    }

    /** The employee / client rows that belong to this user, by role. */
    private function personRows(User $user, bool $withTrashed = false): Collection
    {
        $model = match ($user->role) {
            Role::Employee => Employee::class,
            Role::Client => Client::class,
            default => null,
        };

        if ($model === null) {
            return collect();
        }

        $query = $model::where('user_id', $user->user_id);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->get();
    }
}