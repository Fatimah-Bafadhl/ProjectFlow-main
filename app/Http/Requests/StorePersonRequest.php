<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware (role:admin) already gates this
    }

    public function rules(): array
    {
        $role = $this->input('role');

        return [
            'username' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                function ($attribute, $value, $fail) {
                    $this->failIfEmailTaken($value, null, null, null, $fail);
                },
            ],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'manager', 'employee', 'client'])],
            'phone' => ['required', 'regex:/^05[0-9]{8}$/'],
            'company_name' => [Rule::requiredIf($role === 'client'), 'nullable', 'string', 'max:255'],
            'department' => [Rule::requiredIf($role === 'employee'), 'nullable', 'string', 'max:255'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => [Rule::exists('projects', 'project_id')->whereNull('deleted_at')],
        ];
    }

    /**
     * Shared by Store/UpdatePersonRequest: checks users+employees+clients (trashed included,
     * since these are raw table queries that bypass the SoftDeletes model scope) and gives a
     * friendlier message when the only conflict is a trashed row.
     */
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
}