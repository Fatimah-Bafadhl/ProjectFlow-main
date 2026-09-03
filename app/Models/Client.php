<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $primaryKey = 'client_id';


    protected $fillable = [
    'user_id',
    'name',
    'company_name',
    'email',
    'phone',
    'project_name',
];

      public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

        public function projects()
    {
        return $this->belongsToMany(Project::class, 'client_project', 'client_id', 'project_id');
    }
}

