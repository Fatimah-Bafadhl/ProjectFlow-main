<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;

class ProjectDocumentController extends Controller
{
    public function index($project_id)
    {
        $project = Project::findOrFail($project_id);
        $documents = $project->documents()->latest()->get();

        return view('documents.project-index', compact('project', 'documents'));
    }

        public function store(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

        $validated = $request->validate([
            'type' => 'required|in:link,file',
            'title' => 'required|string|max:255',
            'url' => 'required_if:type,link|nullable|url|max:2048',
            'file' => 'required_if:type,file|nullable|file|max:20480',
        ]);

        $visibleToClient = $request->boolean('visible_to_client');

        $data = [
            'project_id' => $project->project_id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'added_by_user_id' => auth()->id(),
            'added_by_name' => auth()->user()->username,
            'visible_to_client' => $visibleToClient,
        ];

        if ($validated['type'] === 'link') {
            $data['url'] = $validated['url'];
        } else {
            $path = $request->file('file')->store('project_documents', 'public');
            $data['file_path'] = $path;
            $data['original_filename'] = $request->file('file')->getClientOriginalName();
        }

        ProjectDocument::create($data);

        // Auto-post into the client-facing communication thread when the doc is shared.
        if ($visibleToClient) {
            \App\Models\Comment::create([
                'comment_text'      => 'تم رفع تسليم جديد: ' . $validated['title'],
                'attachment'        => null,
                'project_id'        => $project->project_id,
                'user_id'           => auth()->user()->user_id,
                'author_name'       => auth()->user()->username,
                'visible_to_client' => true,
            ]);
        }

        return back()->with('success', 'تم إضافة المستند بنجاح');
    }

    public function destroy($project_document_id)
    {
        $document = ProjectDocument::findOrFail($project_document_id);

               // The file is removed automatically after the commit (see CascadesSoftDeletes).
        $document->forceDelete();

        return back()->with('success', 'تم حذف المستند');
    }
}
