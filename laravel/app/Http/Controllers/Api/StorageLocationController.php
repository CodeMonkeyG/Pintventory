<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StorageLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for managing physical storage locations for inventory items.
 */
class StorageLocationController extends Controller
{
    /**
     * Display a listing of storage locations for the authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return StorageLocation::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();
    }

    /**
     * Store a newly created storage location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
     * Display the specified storage location.
     *
     * @param  \App\Models\StorageLocation  $storageLocation
     * @return \Illuminate\Http\JsonResponse|\App\Models\StorageLocation
     */
    public function show(StorageLocation $storageLocation)
    {
        if ($storageLocation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $storageLocation;
    }

    /**
     * Update the specified storage location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StorageLocation  $storageLocation
     * @return \Illuminate\Http\JsonResponse
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
     * Remove the specified storage location from storage.
     *
     * @param  \App\Models\StorageLocation  $storageLocation
     * @return \Illuminate\Http\JsonResponse
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
