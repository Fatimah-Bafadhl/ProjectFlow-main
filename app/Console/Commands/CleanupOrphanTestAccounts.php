<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off, M0-decided cleanup for the old test database:
 * - clients #1 "fat" and #3 "testcli" (no login, or trashed with a duplicate email)
 * - users #3 "sarah-employee" and #8 "testcli" (role employee/client with no matching person row)
 * Permanent delete. Dry run by default, add --apply to save.
 */
class CleanupOrphanTestAccounts extends Command
{
    protected $signature = 'cleanup:orphan-test-accounts {--apply}';

    protected $description = 'M0-decided one-off: remove specific old test clients/users. Dry run unless --apply is given.';

    private const CLIENT_IDS = [1, 3];

    private const USER_IDS = [3, 8];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLY mode: changes will be saved.' : 'DRY RUN: nothing will be saved. Add --apply to save.');

        DB::beginTransaction();

        try {
            foreach (Client::withTrashed()->whereIn('client_id', self::CLIENT_IDS)->get() as $client) {
                $this->line("  client #{$client->client_id} \"{$client->name}\" <{$client->email}>");
                $client->forceDelete();
            }

            foreach (User::withTrashed()->whereIn('user_id', self::USER_IDS)->get() as $user) {
                $this->line("  user #{$user->user_id} \"{$user->username}\" <{$user->email}> role={$user->role->value}");
                $user->forceDelete();
            }

            if ($apply) {
                DB::commit();
                $this->info('Saved.');
            } else {
                DB::rollBack();
                $this->info('Dry run finished.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return self::SUCCESS;
    }
}