<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Notifications\SystemActivityNotification;
use App\Models\Project;
use App\Http\Requests\UpdatePersonRequest;
use App\Services\PersonProfileService;

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

    public function update(UpdatePersonRequest $request, Client $client, PersonProfileService $accounts)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية تعديل بيانات العميل.');
        }

        if (!$client->user_id) {
            return redirect()->back()->withErrors(['account' => 'لا يمكن تعديل عميل بلا حساب مستخدم.']);
        }

        $accounts->update($client->user, $request->validated());

        if (auth()->check()) {
            auth()->user()->notify(new SystemActivityNotification(
                'تعديل عميل',
                'تم تعديل بيانات العميل: ' . $client->refresh()->name,
                route('clients.index')
            ));
        }

        return redirect()->back()->with('success', 'تم تعديل بيانات العميل بنجاح');
    }

    public function destroy(Client $client, \App\Services\AccountLifecycle $accounts)
    {

     if (!auth()->user()->isAdmin()) {
            abort(403, 'عذراً، لا تمتلك صلاحية حذف عميل.');
        }
        $clientName = $client->name;
        $linkedUserId = $client->user_id;

                try {
            $accounts->delete($client, auth()->user());
        } catch (\App\Services\AccountLifecycleException $e) {
            return redirect()->back()->withErrors(['account' => $e->getMessage()]);
        }

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