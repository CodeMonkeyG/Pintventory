<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of the user's workspaces.
     */
    public function index()
    {
        return Auth::user()->workspaces;
    }

    /**
     * Store a newly created workspace in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'created_by_user_id' => Auth::id(),
        ]);

        // Attach the creator as owner
        $workspace->users()->attach(Auth::id(), ['role' => 'owner']);

        // Optionally switch to the new workspace immediately
        $user = Auth::user();
        $user->current_workspace_id = $workspace->id;
        $user->save();

        return response()->json($workspace, 201);
    }

    /**
     * Update the specified workspace.
     */
    public function update(Request $request, Workspace $workspace)
    {
        // Check if user has permission to update (only owners/admins)
        // For now, let's keep it simple.
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace->update($validated);

        return response()->json($workspace);
    }

    /**
     * Switch the user's current workspace.
     */
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
        ]);

        $user = Auth::user();
        
        // Ensure user belongs to the workspace
        if (!$user->workspaces()->where('workspace_id', $validated['workspace_id'])->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->current_workspace_id = $validated['workspace_id'];
        $user->save();

        return response()->json([
            'message' => 'Workspace switched',
            'current_workspace_id' => $user->current_workspace_id,
            'workspace' => $user->currentWorkspace
        ]);
    }
}
