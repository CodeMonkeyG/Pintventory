<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StorageLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StorageLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return StorageLocation::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $location = StorageLocation::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'user_id' => Auth::id(),
        ]);

        return response()->json($location, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(StorageLocation $storageLocation)
    {
        if ($storageLocation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $storageLocation;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StorageLocation $storageLocation)
    {
        if ($storageLocation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $storageLocation->update($validated);

        return response()->json($storageLocation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StorageLocation $storageLocation)
    {
        if ($storageLocation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $storageLocation->delete();

        return response()->json(null, 204);
    }
}
