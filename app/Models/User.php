<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\Role;

class User extends Authenticatable
{
    use HasFactory, Notifiable, \Illuminate\Database\Eloquent\SoftDeletes;

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'user_id');
    }
    // تحديد المفتاح الرئيسي المخصص لجدولك
    protected $primaryKey = 'user_id';

    /**
     * الحقول المسموح بتعبئتها جماعياً
     */
   protected $fillable = [
    'username',
    'email',
    'password',
    'role',
    'phone',
    'company_name',
];

    /**
     * الحقول المخفية عند تحويل الموديل لـ Array أو JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * تحويل أنواع الحقول
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function client()
{
    return $this->hasOne(Client::class, 'user_id', 'user_id');
}

public function projects()
{
    return $this->hasMany(Project::class);
}

public function tasks()
{
    return $this->hasMany(Task::class);
}

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === Role::Manager;
    }

    public function isEmployee(): bool
    {
        return $this->role === Role::Employee;
    }

    public function isClient(): bool
    {
        return $this->role === Role::Client;
    }
    
    public function managedProjects()
{
    return $this->belongsToMany(Project::class, 'project_user', 'user_id', 'project_id');
}

}