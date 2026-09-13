<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function store(Request $request, $task_id)
    {
        $task = Task::findOrFail($task_id);

        $validated = $request->validate([
            'type'  => 'required|in:link,file',
            'title' => 'required|string|max:255',
            'url'   => 'required_if:type,link|nullable|url|max:2048',
            'file'  => 'required_if:type,file|nullable|file|max:20480',
        ]);

        $data = [
            'task_id'          => $task->task_id,
            'type'             => $validated['type'],
            'title'            => $validated['title'],
            'added_by_user_id' => auth()->id(),
            'added_by_name'    => auth()->user()->username,
        ];

        if ($validated['type'] === 'link') {
            $data['url'] = $validated['url'];
        } else {
            $path = $request->file('file')->store('task_attachments', 'public');
            $data['file_path']         = $path;
            $data['original_filename'] = $request->file('file')->getClientOriginalName();
        }

        TaskAttachment::create($data);

        return back()->with('success', 'تم إضافة المرفق بنجاح');
    }

    public function destroy($task_attachment_id)
    {
        $attachment = TaskAttachment::findOrFail($task_attachment_id);

        if ($attachment->file_path) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return back()->with('success', 'تم حذف المرفق');
    }
}