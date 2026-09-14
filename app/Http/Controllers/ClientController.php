<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Notifications\SystemActivityNotification;
use App\Models\Project;

class ClientController extends Controller
{
    public function index()

    {

    if (!auth()->user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية استعراض هذه الصفحة.');
        }

         $clients = Client::with('projects')->get();
        $projects = Project::all();
        
        // جلب أسماء الشركات الفريدة من جدول المشاريع (Projects)
       

        return view('clients.index', compact('clients', 'projects'));
    }

    public function store(Request $request)
    {    abort(404, 'يتم إنشاء حسابات العملاء من صفحة إدارة المستخدمين فقط.');}

    public function update(Request $request, Client $client)
    {

      if (!auth()->user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية تعديل بيانات العميل.');
        }
         $request->validate([
            'name'          => 'required|string|max:255',
            'company_name'  => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'required|string|max:20',
            'project_ids'   => 'nullable|array',
            'project_ids.*' => 'exists:projects,project_id',
        ]);

               $client->update([
            'name'         => $request->name,
            'company_name' => $request->company_name,
            'email'        => $request->email,
            'phone'        => $request->phone,
        ]);

        if ($client->user_id) {
            User::where('user_id', $client->user_id)->update(['username' => $client->name]);
        }

        $projectIds = $request->input('project_ids', []);
        $client->projects()->sync($projectIds);

        $firstProject = !empty($projectIds) ? Project::find($projectIds[0]) : null;
        $client->update(['project_name' => $firstProject?->project_name]);

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'تعديل عميل',
                'تم تعديل بيانات العميل: ' . $client->name,
                route('clients.index')
            ));
        }

        return redirect()->back()->with('success', 'تم تعديل بيانات العميل بنجاح');
    }

        public function destroy(Client $client)
    {

     if (!auth()->user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية حذف عميل.');
        }
        $clientName = $client->name;
        $linkedUserId = $client->user_id;

        DB::transaction(function () use ($client, $linkedUserId) {
            $client->delete();

            if ($linkedUserId) {
                User::where('user_id', $linkedUserId)->delete();
            }
        });

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'حذف عميل',
                'تم حذف العميل: ' . $clientName,
                route('clients.index')
            ));
        }

        return redirect()->back()->with('success', 'تم حذف العميل بنجاح');
    }
}