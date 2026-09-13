<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\Scm\ScmAccessPermission;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScmAccessController extends Controller
{
    public function index()
    {
        $users = User::whereIn('position', ['secretary', 'special_officer', 'manager'])
            ->with('scmAccess')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'position' => $user->position,
                'can_access_scm' => $user->scmAccess ? $user->scmAccess->can_access_scm : false,
            ]);

        return Inertia::render('Dashboard/SCM/ScmAccess', [
            'users' => $users,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_access_scm' => 'required|boolean',
        ]);

        ScmAccessPermission::updateOrCreate(
            ['user_id' => $validated['user_id']],
            [
                'granted_by' => auth()->id(),
                'can_access_scm' => $validated['can_access_scm'],
            ]
        );

        return redirect()->back()->with('success', 'SCM access updated.');
    }
}