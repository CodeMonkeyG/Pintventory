<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'owner') {
             return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return User::paginate($request->input('per_page', 25));
    }

    public function show(Request $request)
    {
        return $request->user()->load(['workspaces', 'currentWorkspace']);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'current_workspace_id' => 'sometimes|exists:workspaces,id',
            'preferences' => 'nullable|array',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['current_workspace_id'])) {
            // Verify user belongs to this workspace
            if ($user->workspaces()->where('workspaces.id', $validated['current_workspace_id'])->exists()) {
                $user->current_workspace_id = $validated['current_workspace_id'];
            }
        }

        if (isset($validated['preferences'])) {
            $currentPreferences = $user->preferences ?? [];
            $user->preferences = array_merge($currentPreferences, $validated['preferences']);
        }

        $user->save();

        return response()->json($user->load(['workspaces', 'currentWorkspace']));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
