<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $primaryKey = 'ticket_id';

    protected $fillable = ['project_id', 'client_id', 'client_name', 'message', 'status'];
    protected $casts = [
        'status' => TicketStatus::class,
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
}