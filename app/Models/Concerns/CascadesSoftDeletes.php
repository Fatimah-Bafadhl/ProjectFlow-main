<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Soft delete / restore / permanent delete together with child rows.
 *
 * - Soft delete: live children get the parent's exact deleted_at.
 * - Restore: only children with that exact deleted_at come back.
 * - Permanent delete: file paths are collected first, rows are deleted in a
 *   transaction (the database cascades the rows), files are removed after commit.
 *
 * A model lists its children in cascadeRelations() and its file column in purgeFileColumn().
 */
trait CascadesSoftDeletes
{
    use SoftDeletes {
        restore as private restoreRowOnly;
    }

    /** Names of the hasMany relations that are deleted/restored with this model. */
    public function cascadeRelations(): array
    {
        return [];
    }

    /** Column that holds an uploaded file path on the "public" disk, or null. */
    public function purgeFileColumn(): ?string
    {
        return null;
    }

    /** Runs inside the restore transaction, after the children are restored. */
    protected function afterCascadeRestore(): void
    {
    }

    public function delete()
    {
        $force = $this->isForceDeleting();
        $paths = [];

        DB::beginTransaction();

        try {
            if ($force) {
                $paths = $this->collectPurgeFiles();
            }

            $result = parent::delete();

            if ($result && ! $force) {
                $stamp = $this->getRawOriginal($this->getDeletedAtColumn());

                if ($stamp) {
                    $this->cascadeSoftDeleteTo((string) $stamp);
                }
            }

            if ($result && $force && $paths) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($paths));
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $result;
    }

    public function restore()
    {
        $stamp = $this->getRawOriginal($this->getDeletedAtColumn());

        return DB::transaction(function () use ($stamp) {
            $restored = $this->restoreRowOnly();

            if ($restored && $stamp) {
                $this->cascadeRestoreFrom((string) $stamp);
                $this->afterCascadeRestore();
            }

            return $restored;
        });
    }

    /** Soft delete all LIVE children (and their children) with this exact time. Returns the row count. */
    public function cascadeSoftDeleteTo(string $stamp): int
    {
        $count = 0;

        foreach ($this->cascadeRelations() as $name) {
            $related = $this->{$name}()->getRelated();

            if (method_exists($related, 'cascadeSoftDeleteTo')) {
                foreach ($this->{$name}()->get() as $child) {
                    $count += $child->cascadeSoftDeleteTo($stamp);
                }
            }

            $count += $this->{$name}()->toBase()->reorder()
                ->update([$related->getDeletedAtColumn() => $stamp]);
        }

        return $count;
    }

    /** Restore the children (and their children) that were trashed with exactly this time. */
    public function cascadeRestoreFrom(string $stamp): int
    {
        $count = 0;

        foreach ($this->cascadeRelations() as $name) {
            $related = $this->{$name}()->getRelated();
            $column = $related->getQualifiedDeletedAtColumn();

            if (method_exists($related, 'cascadeRestoreFrom')) {
                foreach ($this->{$name}()->withTrashed()->where($column, $stamp)->get() as $child) {
                    $count += $child->cascadeRestoreFrom($stamp);
                }
            }

            $count += $this->{$name}()->withTrashed()->where($column, $stamp)
                ->toBase()->reorder()
                ->update([$related->getDeletedAtColumn() => null]);
        }

        return $count;
    }

    /** File paths of this model and of all its children, including trashed ones. */
    public function collectPurgeFiles(): array
    {
        $paths = [];

        $column = $this->purgeFileColumn();

        if ($column && $this->getAttribute($column)) {
            $paths[] = $this->getAttribute($column);
        }

        foreach ($this->cascadeRelations() as $name) {
            $related = $this->{$name}()->getRelated();

            if (! method_exists($related, 'collectPurgeFiles')) {
                continue;
            }

            foreach ($this->{$name}()->withTrashed()->get() as $child) {
                $paths = array_merge($paths, $child->collectPurgeFiles());
            }
        }

        return $paths;
    }
}