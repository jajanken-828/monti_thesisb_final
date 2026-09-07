<?php

namespace App\Http\Controllers\ord;

use App\Http\Controllers\Controller;
use App\Models\ord\OrdAccess;
use App\Models\core\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrdAccessController extends Controller
{
    public function index()
    {
        // Only CEO can manage ORD access grants.
        // (Replaces the undefined `ceo-only` gate, which denied everyone.)
        abort_unless(auth()->user()?->role === 'CEO', 403, 'Only the CEO can manage ORD access.');

        $candidateRoles = ['secretary', 'general manager', 'manager'];
        $users = User::whereIn('position', $candidateRoles)
            ->orWhere(function ($q) {
                $q->where('role', 'SCM')->where('position', 'manager');
            })
            ->get();

        $accessList = OrdAccess::pluck('can_access_ord', 'user_id')->toArray();

        $users = $users->map(function ($user) use ($accessList) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'position' => $user->position,
                'has_access' => $accessList[$user->id] ?? false,
            ];
        });

        return Inertia::render('Dashboard/ORD/Access', [
            'users' => $users,
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()?->role === 'CEO', 403, 'Only the CEO can manage ORD access.');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_access' => 'required|boolean',
        ]);

        OrdAccess::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'granted_by' => auth()->id(),
                'can_access_ord' => $request->can_access,
            ]
        );

        return redirect()->back()->with('success', 'Access updated successfully.');
    }
}