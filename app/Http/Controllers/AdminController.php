<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    public function approve(User $user)
    {
        Gate::authorize('is-admin');

        $user->is_official = true;
        $user->official_request = 'approved';
        $user->save();

        AuditLog::create([
            'admin_id' => Auth::id(),
            'action' => 'Approved official account',
            'target_table' => 'users',
            'target_id' => $user->id,
        ]);

        return response()->json(['message' => 'Approved']);
    }

    public function reject(User $user)
    {
        Gate::authorize('is-admin');

        $user->official_request = 'rejected';
        $user->save();

        AuditLog::create([
            'admin_id' => Auth::id(),
            'action' => 'Rejected official account',
            'target_table' => 'users',
            'target_id' => $user->id,
        ]);

        return response()->json(['message' => 'Rejected']);
    }

    public function destroy(User $user)
    {
        Gate::authorize('is-admin');

        $user->delete();

        AuditLog::create([
            'admin_id' => Auth::id(),
            'action' => 'Deleted user',
            'target_table' => 'users',
            'target_id' => $user->id,
        ]);

        return response()->json(['message' => 'Deleted']);
    }

    public function logs()
    {
        Gate::authorize('is-admin');

        return AuditLog::latest()->get();
    }
}
