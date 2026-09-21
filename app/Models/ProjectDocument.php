<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDocument extends Model
{
    use HasFactory, \App\Models\Concerns\CascadesSoftDeletes;

    public function purgeFileColumn(): ?string
    {
        return 'file_path';
    }

    protected $primaryKey = 'project_document_id';

    protected $fillable = [
        'project_id',
        'type',
        'title',
        'url',
        'file_path',
        'original_filename',
        'added_by_user_id',
        'added_by_name',
        'visible_to_client',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'visible_to_client' => 'boolean',
        ];
    }

    public function scopeVisibleToClient($query)
    {
        return $query->where('visible_to_client', true);
    }
}