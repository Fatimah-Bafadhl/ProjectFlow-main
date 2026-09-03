<?php

namespace App\Http\Controllers;

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
        $companies = Project::select('company_name')->distinct()->get();

        return view('clients.index', compact('clients', 'projects', 'companies'));
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
            'project_ids'   => 'required|array|min:1',
            'project_ids.*' => 'exists:projects,project_id',
        ]);

        $client->update([
            'name'         => $request->name,
            'company_name' => $request->company_name,
            'email'        => $request->email,
            'phone'        => $request->phone,
        ]);

        $client->projects()->sync($request->project_ids);

        $firstProject = Project::find($request->project_ids[0]);
        if ($firstProject) {
            $client->update(['project_name' => $firstProject->project_name]);
        }

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
        $client->delete();

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