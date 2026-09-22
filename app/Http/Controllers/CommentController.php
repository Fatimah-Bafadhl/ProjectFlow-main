<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Task;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Notifications\SystemActivityNotification;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function store(Request $request, $task_id)

    {
     $user = auth()->user();

        if ($user && $user->isClient()) {
            abort(403, 'عذراً، لا تمتلك صلاحية إضافة تعليقات.');
        }

        $task = Task::findOrFail($task_id);

        if ($user->isManager()) {
            if (!$user->managedProjects()->where('projects.project_id', $task->project_id)->exists()) {
                abort(403, 'عذراً، لا تمتلك صلاحية إضافة تعليقات على هذه المهمة.');
            }
                } elseif ($user->isEmployee()) {
            $employee = Employee::where('user_id', $user->user_id)->first();
            $employeeId = $employee->employee_id ?? 0;
            if (! $task->assignedEmployees()->where('employees.employee_id', $employeeId)->exists()) {
                abort(403, 'عذراً، لا تمتلك صلاحية إضافة تعليقات على هذه المهمة.');
            }
        }
        $request->validate([
            'comment_text' => 'nullable|string',
            'attachment'   => 'nullable|file|mimes:pdf,doc,docx,zip,fig|max:10240',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10240',
        ]);

                                $userId = $user->user_id;
                $authorName = $userId ? $user->username : null;
                $visibleToClient = false;
        // Task comments are internal-only now; project-level comments are the sole client-facing channel.

        if (empty($request->comment_text) && !$request->hasFile('attachment') && !$request->hasFile('image')) {
            return back()->withErrors(['comment_text' => 'يجب كتابة تعليق أو إرفاق ملف أو صورة.']);
        }

        // إذا تم إرفاق ملف
        if ($request->hasFile('attachment')) {
            $pathFile = $request->file('attachment')->store('comments', 'public');
            Comment::create([
                'comment_text' => $request->comment_text ?? '',
                'attachment'   => $pathFile,
                'task_id'      => $task_id,
                'user_id'      => $userId,
                'author_name'  => $authorName,
                'visible_to_client' => $visibleToClient,
            ]);
            $request->merge(['comment_text' => '']);
        }

        // إذا تم إرفاق صورة
        if ($request->hasFile('image')) {
            $pathImage = $request->file('image')->store('comments', 'public');
            Comment::create([
                'comment_text' => $request->comment_text ?? '',
                'attachment'   => $pathImage,
                'task_id'      => $task_id,
                'user_id'      => $userId,
                'author_name'  => $authorName,
                'visible_to_client' => $visibleToClient,
            ]);
        }

        // إذا لم يتم إرفاق لا ملف ولا صورة بل نص فقط
        if (!$request->hasFile('attachment') && !$request->hasFile('image')) {
            Comment::create([
                'comment_text' => $request->comment_text,
                'attachment'   => null,
                'task_id'      => $task_id,
                'user_id'      => $userId,
                'author_name'  => $authorName,
                'visible_to_client' => $visibleToClient,
            ]);
        }

        if (auth()->check()) {
            try {
                auth()->user()->notify(new SystemActivityNotification(
                    'تعليق جديد',
                    'تم إضافة تعليق جديد على المهمة'
                ));
            } catch (\Exception $e) {}
        }

        return redirect()->back();
    }

        public function storeForProject(Request $request, $project_id)
    {
        $user = auth()->user();

        $project = \App\Models\Project::findOrFail($project_id);

        $isAssignedManager = $project->managers()->where('users.user_id', $user->user_id)->exists();

        if (! $user->isAdmin() && ! $isAssignedManager) {
            abort(403, 'عذراً، لا تمتلك صلاحية إضافة تعليقات على هذا المشروع.');
        }

        $request->validate([
            'comment_text' => 'nullable|string',
            'attachment'   => 'nullable|file|mimes:pdf,doc,docx,zip,fig,jpg,jpeg,png,gif|max:10240',
        ]);

        if (empty($request->comment_text) && !$request->hasFile('attachment')) {
            return back()->withErrors(['comment_text' => 'يجب كتابة تعليق أو إرفاق ملف.']);
        }

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('comments', 'public');
        }

        Comment::create([
            'comment_text'       => $request->comment_text ?? '',
            'attachment'         => $path,
            'project_id'         => $project->project_id,
            'user_id'            => $user->user_id,
            'author_name' => $user->username,
            'visible_to_client'  => true,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        // التحقق من أن المستخدم هو صاحب التعليق
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'comment_text' => 'nullable|string',
            'attachment'   => 'nullable|file|mimes:pdf,doc,docx,zip,fig,jpg,jpeg,png,gif|max:10240',
        ]);

        if (empty($request->comment_text) && !$request->hasFile('attachment') && !$comment->attachment && $request->input('remove_attachment') != '1') {
            return back()->withErrors(['comment_text' => 'يجب كتابة تعليق أو إرفاق ملف أو صورة.']);
        }

        // إذا طلب المستخدم إزالة المرفق الحالي
        if ($request->input('remove_attachment') == '1') {
            if ($comment->attachment && Storage::disk('public')->exists($comment->attachment)) {
                Storage::disk('public')->delete($comment->attachment);
            }
            $comment->attachment = null;
        }

        // إذا تم إرفاق ملف أو صورة جديدة للاستبدال
        if ($request->hasFile('attachment')) {
            if ($comment->attachment && Storage::disk('public')->exists($comment->attachment)) {
                Storage::disk('public')->delete($comment->attachment);
            }

            $path = $request->file('attachment')->store('comments', 'public');
            $comment->attachment = $path;
        }

        $comment->comment_text = $request->comment_text ?? '';
        $comment->save();

        return redirect()->back();
    }

     public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $user = auth()->user();

        $project = $comment->task_id ? $comment->task->project : $comment->project;
        $isProjectManager = $project && $user->isManager()
            && $project->managers()->where('users.user_id', $user->user_id)->exists();

        if ($comment->user_id !== $user->user_id && !$user->isAdmin() && !$isProjectManager) {
            abort(403, 'عذراً، لا تمتلك صلاحية حذف هذا التعليق.');
        }

                // The file is removed automatically after the commit (see CascadesSoftDeletes).
        $comment->forceDelete();

        return redirect()->back();
    }
}