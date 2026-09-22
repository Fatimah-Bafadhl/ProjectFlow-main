<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware + each controller's own admin/ownership checks already gate this
    }

    /** Resolves the User being edited from whichever route-bound model this request came through. */
    protected function targetUser(): ?User
    {
        $route = $this->route();

        foreach (['user', 'employee', 'client'] as $param) {
            if ($route->hasParameter($param)) {
                $bound = $route->parameter($param);
                if ($bound instanceof User) return $bound;
                if ($bound instanceof Employee) return $bound->user;
                if ($bound instanceof Client) return $bound->user;
            }
        }

        // profile.update has no route parameter — the target is always the logged-in user.
        return auth()->user();
    }

    public function rules(): array
    {
        $target = $this->targetUser();
        $employeeId = $target?->employee?->employee_id;
        $clientId = $target?->client?->client_id;

        // If this page doesn't submit a role field (Employee/Client/Profile pages), fall back
        // to the person's current role so department/company_name still get validated correctly.
        $role = $this->input('role', $target?->role?->value);

        return [
            'username' => ['required_without:name', 'nullable', 'string', 'max:255'],
            'name' => ['required_without:username', 'nullable', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                function ($attribute, $value, $fail) use ($target, $employeeId, $clientId) {
                    $this->failIfEmailTaken($value, $target?->user_id, $employeeId, $clientId, $fail);
                },
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'required', Rule::in(['admin', 'manager', 'employee', 'client']),
                function ($attribute, $value, $fail) use ($target) {
                    $this->failIfRoleChangeNotAllowed($target, $value, $fail);
                },
            ],
            'phone' => ['required', 'regex:/^05[0-9]{8}$/'],
            'company_name' => [Rule::requiredIf($role === 'client'), 'nullable', 'string', 'max:255'],
            'department' => [Rule::requiredIf($role === 'employee'), 'nullable', 'string', 'max:255'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => [Rule::exists('projects', 'project_id')->whereNull('deleted_at')],
        ];
    }

    /** Same email-uniqueness logic as StorePersonRequest, duplicated here since Form Requests don't share a common base without one. */
    protected function failIfEmailTaken(string $email, ?int $ignoreUserId, ?int $ignoreEmployeeId, ?int $ignoreClientId, \Closure $fail): void
    {
        $userMatch = DB::table('users')->where('email', $email)
            ->when($ignoreUserId, fn ($q) => $q->where('user_id', '!=', $ignoreUserId))
            ->first();
        $employeeMatch = DB::table('employees')->where('email', $email)
            ->when($ignoreEmployeeId, fn ($q) => $q->where('employee_id', '!=', $ignoreEmployeeId))
            ->first();
        $clientMatch = DB::table('clients')->where('email', $email)
            ->when($ignoreClientId, fn ($q) => $q->where('client_id', '!=', $ignoreClientId))
            ->first();

        $match = $userMatch ?? $employeeMatch ?? $clientMatch;

        if ($match) {
            $isTrashed = !is_null($match->deleted_at ?? null);
            $fail($isTrashed
                ? 'هذا البريد الإلكتروني مستخدم لعنصر موجود في المحذوفات. يمكنك استعادته من صفحة المحذوفات إن أردت.'
                : 'هذا البريد الإلكتروني مستخدم من قبل.');
        }
    }

    /**
     * Decision 7: only manager<->employee switching is allowed. Admin and client are locked
     * both directions. Nobody can change their own role. Decision 8: the last active admin
     * can never be demoted.
     */
    protected function failIfRoleChangeNotAllowed(?User $target, string $newRole, \Closure $fail): void
    {
        if (!$target || $newRole === $target->role->value) {
            return; // no actual change requested
        }

        if ($target->user_id === auth()->id()) {
            $fail('لا يمكنك تغيير صلاحيتك الخاصة.');
            return;
        }

        $oldRole = $target->role->value;
        $allowed = [
            'manager' => ['manager', 'employee'],
            'employee' => ['employee', 'manager'],
        ];

        if (!isset($allowed[$oldRole]) || !in_array($newRole, $allowed[$oldRole], true)) {
            $fail('لا يمكن تغيير هذه الصلاحية. يُسمح فقط بالتبديل بين موظف ومدير.');
            return;
        }

        if ($oldRole === Role::Admin->value) {
            // Redundant with the branch above (admin isn't in $allowed), kept explicit for clarity.
            $fail('لا يمكن تغيير صلاحية المدير العام.');
            return;
        }

        if ($oldRole === 'manager' && $newRole === 'employee') {
            $activeAdmins = User::where('role', Role::Admin->value)->count();
            if ($target->role === Role::Admin && $activeAdmins <= 1) {
                $fail('لا يمكن تغيير صلاحية آخر مدير عام نشط.');
            }
        }
    }
}