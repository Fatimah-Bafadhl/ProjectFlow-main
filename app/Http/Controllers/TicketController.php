<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function store(Request $request, $project_id)
    {
        $user = auth()->user();

        if (! $user->isClient()) {
            abort(403, 'عذراً، لا تمتلك صلاحية إرسال طلب.');
        }

                                $client = $user->client;
        //$client = Client::where('user_id', $user->user_id)->first();

        $project = Project::findOrFail($project_id);

        if (! $client || ! $client->projects()->where('projects.project_id', $project->project_id)->exists()) {
            abort(403, 'عذراً، ليس لديك صلاحية إرسال طلب على هذا المشروع.');
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        Ticket::create([
            'project_id' => $project->project_id,
            'client_id'  => $client->client_id,
            'client_name' => $client->name,
            'message'    => $request->message,
            'status'     => TicketStatus::Open->value,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $ticket_id)
    {
        $user = auth()->user();

        $ticket = Ticket::findOrFail($ticket_id);

        $isAssignedManager = $ticket->project->managers()->where('users.user_id', $user->user_id)->exists();

        if (! $user->isAdmin() && ! $isAssignedManager) {
            abort(403, 'عذراً، لا تمتلك صلاحية تعديل هذا الطلب.');
        }

        $ticket->update([
            'status' => TicketStatus::Handled->value,
        ]);

        return redirect()->back();
    }
}