<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskAttachment extends Model
{
    use HasFactory, \App\Models\Concerns\CascadesSoftDeletes;

    public function purgeFileColumn(): ?string
    {
        return 'file_path';
    }

    protected $primaryKey = 'task_attachment_id';

    protected $fillable = [
        'task_id',
        'type',
        'title',
        'url',
        'file_path',
        'original_filename',
        'added_by_user_id',
        'added_by_name',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}