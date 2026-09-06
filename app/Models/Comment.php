<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
     use HasFactory;

         protected static function booted(): void
    {
        static::saving(function (Comment $comment) {
            $hasTask = ! is_null($comment->task_id);
            $hasProject = ! is_null($comment->project_id);

            if ($hasTask === $hasProject) {
                throw new \RuntimeException(
                    'A comment must belong to exactly one of task_id or project_id, not both or neither.'
                );
            }
        });
    }

     protected $primaryKey = 'comment_id';


   protected $fillable = ['comment_text', 'attachment', 'task_id', 'project_id', 'user_id', 'author_name', 'visible_to_client'];

    
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

        public function project()
         { return $this->belongsTo(Project::class, 'project_id', 'project_id'); }

  
   public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

