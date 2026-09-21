<?php

namespace App\Console\Commands;

use App\Enums\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * M0 audit. READ-ONLY: this command only runs SELECT queries and reads
 * the file list of the "public" disk. It never changes data or files.
 */
class AuditProjectFlow extends Command
{
    protected $signature = 'audit:projectflow {--limit=25 : Max rows to print per check}';

    protected $description = 'Read-only audit of ProjectFlow data (M0). Changes nothing.';

    private const PHONE_REGEX = '/^05[0-9]{8}$/';

    private const EXPECTED_STAGES = 7;

    private int $limit = 25;

    private int $flagged = 0;

    public function handle(): int
    {
        $this->limit = max(1, (int) $this->option('limit'));

        $this->info('ProjectFlow audit (read-only) - '.now()->toDateTimeString());
        $this->line('Database: '.DB::connection()->getDatabaseName());

        $this->section('0. Row counts (all rows, including trashed)');
        foreach (['users', 'employees', 'clients', 'projects', 'project_stages', 'tasks', 'task_employee', 'comments', 'tickets', 'project_documents', 'task_attachments'] as $table) {
            $this->line(sprintf('   %-18s %d', $table, DB::table($table)->count()));
        }

        $this->checkPeopleLinks();
        $this->checkPeopleMismatch();
        $this->checkTrashedParents();
        $this->checkDuplicates();
        $this->checkPhones();
        $this->checkProjects();
        $this->checkFiles();

        $this->newLine();
        $this->info("Done. Total flagged items: {$this->flagged}");

        return self::SUCCESS;
    }

    private function checkPeopleLinks(): void
    {
        $employeeRole = Role::Employee->value;
        $clientRole = Role::Client->value;

        $this->section('1. People rows without a user');
        $this->report('employees with NULL user_id', DB::table('employees')->whereNull('user_id')->get(['employee_id', 'name', 'email', 'deleted_at']));
        $this->report('clients with NULL user_id', DB::table('clients')->whereNull('user_id')->get(['client_id', 'name', 'email', 'deleted_at']));

        $this->section('2. Role and person-row consistency');
        $this->report("users with role '{$employeeRole}' and no employees row", DB::table('users as u')
            ->where('u.role', $employeeRole)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('employees as e')->whereColumn('e.user_id', 'u.user_id'))
            ->get(['u.user_id', 'u.username', 'u.email']));
        $this->report("users with role '{$clientRole}' and no clients row", DB::table('users as u')
            ->where('u.role', $clientRole)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('clients as c')->whereColumn('c.user_id', 'u.user_id'))
            ->get(['u.user_id', 'u.username', 'u.email']));
        $this->report("employees rows whose user role is not '{$employeeRole}'", DB::table('employees as e')
            ->join('users as u', 'u.user_id', '=', 'e.user_id')
            ->where('u.role', '!=', $employeeRole)
            ->get(['e.employee_id', 'e.name', 'e.deleted_at', 'u.user_id', 'u.role as user_role']));
        $this->report("clients rows whose user role is not '{$clientRole}'", DB::table('clients as c')
            ->join('users as u', 'u.user_id', '=', 'c.user_id')
            ->where('u.role', '!=', $clientRole)
            ->get(['c.client_id', 'c.name', 'c.deleted_at', 'u.user_id', 'u.role as user_role']));
        $this->report('users with more than one employees row', DB::table('employees')->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as rows_count'))->groupBy('user_id')->having('rows_count', '>', 1)->get());
        $this->report('users with more than one clients row', DB::table('clients')->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as rows_count'))->groupBy('user_id')->having('rows_count', '>', 1)->get());
        $this->report('INFO: trashed employees rows whose user still exists (role-change history?)', DB::table('employees as e')
            ->join('users as u', 'u.user_id', '=', 'e.user_id')->whereNotNull('e.deleted_at')
            ->get(['e.employee_id', 'e.name', 'e.deleted_at', 'u.user_id', 'u.role as user_role']));
        $this->report('INFO: trashed clients rows whose user still exists', DB::table('clients as c')
            ->join('users as u', 'u.user_id', '=', 'c.user_id')->whereNotNull('c.deleted_at')
            ->get(['c.client_id', 'c.name', 'c.deleted_at', 'u.user_id', 'u.role as user_role']));
    }

    private function checkPeopleMismatch(): void
    {
        $this->section('3. Name / email / phone differences between user and person row');
        $this->report('employees differing from their user', DB::table('employees as e')
            ->join('users as u', 'u.user_id', '=', 'e.user_id')
            ->where(function ($q) {
                $q->whereColumn('e.name', '!=', 'u.username')
                    ->orWhereColumn('e.email', '!=', 'u.email')
                    ->orWhereRaw("COALESCE(e.phone, '') <> COALESCE(u.phone, '')");
            })
            ->get(['e.employee_id', 'e.deleted_at', 'e.name as person_name', 'u.username as user_name', 'e.email as person_email', 'u.email as user_email', 'e.phone as person_phone', 'u.phone as user_phone']));
        $this->report('clients differing from their user', DB::table('clients as c')
            ->join('users as u', 'u.user_id', '=', 'c.user_id')
            ->where(function ($q) {
                $q->whereColumn('c.name', '!=', 'u.username')
                    ->orWhereColumn('c.email', '!=', 'u.email')
                    ->orWhereRaw("COALESCE(c.phone, '') <> COALESCE(u.phone, '')");
            })
            ->get(['c.client_id', 'c.deleted_at', 'c.name as person_name', 'u.username as user_name', 'c.email as person_email', 'u.email as user_email', 'c.phone as person_phone', 'u.phone as user_phone']));
    }

    private function checkTrashedParents(): void
    {
        $this->section('4. Live children of trashed parents');
        $this->line('   (comments and tickets have no deleted_at yet, so every one of them counts as live)');

        $this->report('live stages in trashed projects', DB::table('project_stages as s')
            ->join('projects as p', 'p.project_id', '=', 's.project_id')
            ->whereNotNull('p.deleted_at')->whereNull('s.deleted_at')
            ->get(['s.project_stage_id', 's.project_id']));
        $this->report('live tasks in trashed projects', DB::table('tasks as t')
            ->join('projects as p', 'p.project_id', '=', 't.project_id')
            ->whereNotNull('p.deleted_at')->whereNull('t.deleted_at')
            ->get(['t.task_id', 't.task_title', 't.project_id']));
        $this->report('live documents in trashed projects', DB::table('project_documents as d')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->whereNotNull('p.deleted_at')->whereNull('d.deleted_at')
            ->get(['d.project_document_id', 'd.title', 'd.project_id']));
        $this->report('project comments in trashed projects', DB::table('comments as c')
            ->join('projects as p', 'p.project_id', '=', 'c.project_id')
            ->whereNotNull('p.deleted_at')
            ->get(['c.comment_id', 'c.project_id']));
        $this->report('tickets in trashed projects', DB::table('tickets as k')
            ->join('projects as p', 'p.project_id', '=', 'k.project_id')
            ->whereNotNull('p.deleted_at')
            ->get(['k.ticket_id', 'k.project_id']));
        $this->report('live attachments of trashed tasks (or tasks of trashed projects)', DB::table('task_attachments as a')
            ->join('tasks as t', 't.task_id', '=', 'a.task_id')
            ->join('projects as p', 'p.project_id', '=', 't.project_id')
            ->whereNull('a.deleted_at')
            ->where(fn ($q) => $q->whereNotNull('t.deleted_at')->orWhereNotNull('p.deleted_at'))
            ->get(['a.task_attachment_id', 'a.task_id', 't.deleted_at as task_deleted', 'p.deleted_at as project_deleted']));
        $this->report('task comments of trashed tasks (or tasks of trashed projects)', DB::table('comments as c')
            ->join('tasks as t', 't.task_id', '=', 'c.task_id')
            ->join('projects as p', 'p.project_id', '=', 't.project_id')
            ->where(fn ($q) => $q->whereNotNull('t.deleted_at')->orWhereNotNull('p.deleted_at'))
            ->get(['c.comment_id', 'c.task_id', 't.deleted_at as task_deleted', 'p.deleted_at as project_deleted']));
    }

    private function checkDuplicates(): void
    {
        $this->section('5. Duplicates');

        // A person row linked to a user is expected to share the user's email.
        // So linked rows are grouped with their user, and only different groups count as duplicates.
        $entries = collect();
        foreach (DB::table('users')->get(['user_id', 'email']) as $u) {
            $entries->push(['email' => $u->email, 'group' => 'U'.$u->user_id, 'label' => "user#{$u->user_id}"]);
        }
        foreach (DB::table('employees')->get(['employee_id', 'email', 'user_id', 'deleted_at']) as $e) {
            $entries->push(['email' => $e->email, 'group' => $e->user_id ? 'U'.$e->user_id : 'E'.$e->employee_id, 'label' => "employee#{$e->employee_id}".($e->deleted_at ? ' (trashed)' : '')]);
        }
        foreach (DB::table('clients')->get(['client_id', 'email', 'user_id', 'deleted_at']) as $c) {
            $entries->push(['email' => $c->email, 'group' => $c->user_id ? 'U'.$c->user_id : 'C'.$c->client_id, 'label' => "client#{$c->client_id}".($c->deleted_at ? ' (trashed)' : '')]);
        }

        $this->report('emails used by different people across users/employees/clients (case-insensitive, trashed included)', $entries
            ->groupBy(fn ($x) => mb_strtolower(trim($x['email'])))
            ->filter(fn ($g) => $g->pluck('group')->unique()->count() > 1)
            ->map(fn ($g, $email) => $email.'  =>  '.$g->pluck('label')->implode(', ')));

        $this->report('duplicate (task_id, employee_id) pairs in task_employee', DB::table('task_employee')
            ->select('task_id', 'employee_id', DB::raw('COUNT(*) as copies'))
            ->groupBy('task_id', 'employee_id')->having('copies', '>', 1)->get());
    }

    private function checkPhones(): void
    {
        $this->section('6. Phones (rule: 05 + 8 digits)');

        $this->report('users with empty phone', DB::table('users')
            ->where(fn ($q) => $q->whereNull('phone')->orWhereRaw("TRIM(phone) = ''"))
            ->get(['user_id', 'username', 'role', 'email']));
        $this->report('INFO: users with a phone that does not match the rule', DB::table('users')
            ->whereNotNull('phone')->whereRaw("TRIM(phone) <> ''")->get(['user_id', 'username', 'role', 'phone'])
            ->reject(fn ($r) => preg_match(self::PHONE_REGEX, $r->phone)));
        $this->report('employees rows with a phone that does not match the rule', DB::table('employees')
            ->get(['employee_id', 'name', 'phone', 'deleted_at'])
            ->reject(fn ($r) => preg_match(self::PHONE_REGEX, (string) $r->phone)));
        $this->report('clients rows with a phone that does not match the rule', DB::table('clients')
            ->get(['client_id', 'name', 'phone', 'deleted_at'])
            ->reject(fn ($r) => preg_match(self::PHONE_REGEX, (string) $r->phone)));
    }

    private function checkProjects(): void
    {
        $this->section('7. Projects');

        $this->report('projects with archived_at set', DB::table('projects')
            ->whereNotNull('archived_at')->get(['project_id', 'project_name', 'archived_at', 'deleted_at']));

        $expected = self::EXPECTED_STAGES;
        $this->report("projects with a stage count different from {$expected}", DB::table('projects as p')
            ->leftJoin('project_stages as s', 's.project_id', '=', 'p.project_id')
            ->groupBy('p.project_id', 'p.project_name', 'p.deleted_at')
            ->select('p.project_id', 'p.project_name', 'p.deleted_at',
                DB::raw('COUNT(s.project_stage_id) as stages_total'),
                DB::raw('SUM(CASE WHEN s.project_stage_id IS NOT NULL AND s.deleted_at IS NULL THEN 1 ELSE 0 END) as stages_live'))
            ->get()
            ->filter(fn ($r) => (int) $r->stages_total !== $expected
                || (is_null($r->deleted_at) && (int) $r->stages_live !== $expected)));

        $this->report('live tasks with zero visible assignees (assignments = rows in task_employee, including trashed employees)', DB::table('tasks as t')
            ->whereNull('t.deleted_at')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('task_employee as te')
                ->join('employees as e', 'e.employee_id', '=', 'te.employee_id')
                ->whereColumn('te.task_id', 't.task_id')->whereNull('e.deleted_at'))
            ->select('t.task_id', 't.task_title', 't.project_id',
                DB::raw('(SELECT COUNT(*) FROM task_employee x WHERE x.task_id = t.task_id) as assignments'))
            ->get());
    }

    private function checkFiles(): void
    {
        $this->section('8. Files on the public disk');
        $disk = Storage::disk('public');

        $rows = collect();
        foreach (DB::table('project_documents')->whereNotNull('file_path')->where('file_path', '!=', '')->get(['project_document_id as id', 'file_path as path', 'deleted_at']) as $r) {
            $rows->push(['table' => 'project_documents', 'id' => $r->id, 'path' => $r->path, 'trashed' => ! is_null($r->deleted_at)]);
        }
        foreach (DB::table('task_attachments')->whereNotNull('file_path')->where('file_path', '!=', '')->get(['task_attachment_id as id', 'file_path as path', 'deleted_at']) as $r) {
            $rows->push(['table' => 'task_attachments', 'id' => $r->id, 'path' => $r->path, 'trashed' => ! is_null($r->deleted_at)]);
        }
        foreach (DB::table('comments')->whereNotNull('attachment')->where('attachment', '!=', '')->get(['comment_id as id', 'attachment as path']) as $r) {
            $rows->push(['table' => 'comments', 'id' => $r->id, 'path' => $r->path, 'trashed' => false]);
        }

        $this->report('rows whose file is missing on disk', $rows
            ->filter(fn ($r) => ! $disk->exists($this->normalize($r['path'])))
            ->map(fn ($r) => "{$r['table']}#{$r['id']}".($r['trashed'] ? ' (row trashed)' : '').'  ->  '.$r['path']));

        $referenced = $rows->map(fn ($r) => $this->normalize($r['path']))->flip();
        $onDisk = collect(['project_documents', 'task_attachments', 'comments'])
            ->flatMap(fn ($dir) => $disk->directoryExists($dir) ? $disk->allFiles($dir) : [])
            ->reject(fn ($f) => str_starts_with(basename($f), '.'));

        $this->report('files on disk that no row references', $onDisk->reject(fn ($f) => $referenced->has($f))->values());
    }

    private function normalize(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->info("=== {$title} ===");
    }

    private function report(string $title, $rows): void
    {
        $rows = collect($rows)->values();

        if ($rows->isEmpty()) {
            $this->line("[OK]    {$title}: 0");

            return;
        }

        $this->flagged += $rows->count();
        $this->warn("[FOUND] {$title}: {$rows->count()}");

        foreach ($rows->take($this->limit) as $row) {
            $this->line('        - '.(is_string($row) ? $row : json_encode((array) $row, JSON_UNESCAPED_UNICODE)));
        }

        if ($rows->count() > $this->limit) {
            $this->line('        ... and '.($rows->count() - $this->limit).' more (run with --limit=500 to see more)');
        }
    }
}