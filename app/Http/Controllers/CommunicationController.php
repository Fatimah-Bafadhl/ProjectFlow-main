<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $client = $user->client;

        if (! $client) {
            abort(403, 'عذراً، لا تمتلك صلاحية الوصول إلى هذه الصفحة.');
        }

        // The client's projects, most recently updated first.
        $projects = $client->projects()
            ->with('managers')
            ->latest('updated_at')
            ->get();

               // Selected project comes from ?project=ID; fall back to the first one.
        $selectedProjectId = (int) $request->query('project');
        $selectedProject = $projects->firstWhere('project_id', $selectedProjectId)
            ?? $projects->first();

        // Optional stage prefill: ?stage=ID → build a starting message from the stage's label.
        // The label is taken from the enum so it always matches the rest of the app.
        $prefillMessage = '';
        $prefillStageId = (int) $request->query('stage');
        if ($selectedProject && $prefillStageId) {
            $stage = $selectedProject->stages()
                ->where('project_stage_id', $prefillStageId)
                ->first();

            if ($stage) {
                $prefillMessage = 'استفسار بخصوص مرحلة ' . $stage->stage_key->label() . ': ';
            }
        }

        // Build the merged thread: tickets from the client + client-visible manager comments.
        $thread = collect();

        if ($selectedProject) {
            $tickets = $selectedProject->tickets()
                ->with('client.user')
                ->get()
                ->map(fn ($t) => (object) [
                    'type'       => 'ticket',
                    'created_at' => $t->created_at,
                    'data'       => $t,
                ]);

            $comments = $selectedProject->comments()
                ->whereNull('task_id')
                ->where('visible_to_client', true)
                ->with('user')
                ->get()
                ->map(fn ($c) => (object) [
                    'type'       => 'comment',
                    'created_at' => $c->created_at,
                    'data'       => $c,
                ]);

            $thread = $tickets->concat($comments)
                ->sortBy('created_at')
                ->values();
        }

        return view('communications.index', compact('projects', 'selectedProject', 'thread', 'prefillMessage'));
        }
}