<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $inventoryItemId)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // Max 10MB
            'caption' => 'nullable|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($inventoryItemId);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store('inventory-photos', 'public');

            $photo = $item->photos()->create([
                'storage_key' => $path,
                'mime_type' => $file->getClientMimeType(),
                'caption' => $request->input('caption'),
                'sort_order' => $item->photos()->count() + 1,
            ]);

            return response()->json($photo, 201);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $photo = Photo::findOrFail($id);

        // Verify ownership via the parent inventory item
        // The InventoryItem model has a UserScope, so retrieving it will fail/return null if not owned.
        if (!$photo->inventoryItem) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($photo->storage_key)) {
            Storage::disk('public')->delete($photo->storage_key);
        }

        $photo->delete();

        return response()->json(['message' => 'Photo deleted']);
    }
}
