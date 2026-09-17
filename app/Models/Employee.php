<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;


class Employee extends Model
{
    use HasFactory,SoftDeletes;

    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'user_id',
        'name',
        'department', // القسم بدلاً من اسم الشركة
        'email',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function projects()
{
    return $this->belongsToMany(Project::class, 'project_employee', 'employee_id', 'project_id');
}

     public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_employee', 'employee_id', 'task_id');
    }
}

