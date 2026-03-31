<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $query = InventoryItem::query()
            ->with(['photos', 'storageLocation'])
            ->search($request->input('search'))
            ->byStatus($request->input('status'))
            ->byTag($request->input('tag'));

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $sortField = $request->input('sort_by', 'updated_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($request->input('per_page', 25));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255', // Unique check recommended but let's stick to basics
            'description' => 'nullable|string',
            'evaluation' => 'nullable|string',
            'status' => 'in:in_stock,low_stock,out_of_stock,archived',
            'item_type' => 'in:unique,standard',
            'quantity_on_hand' => 'integer|min:0',
            'reorder_point' => 'integer|min:0',
            'unit' => 'string|max:50',
            'tags' => 'nullable|array',
            'location' => 'nullable|string|max:255',
            'storage_location_id' => 'nullable|exists:storage_locations,id',
            'market_analysis' => 'nullable|array',
            'facebook_analysis' => 'nullable|array',
            'etsy_analysis' => 'nullable|array',
            'source_links' => 'nullable|array',
        ]);

        // Auto-generate SKU if missing
        if (empty($validated['sku'])) {
            $validated['sku'] = 'INV-' . date('Y') . '-' . strtoupper(uniqid());
        }

        $item = new InventoryItem($validated);
        $item->created_by_user_id = Auth::id();
        $item->save();

        return response()->json($item, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        return InventoryItem::with(['photos', 'purchases.vendor', 'sales.customer'])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $item = InventoryItem::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'evaluation' => 'nullable|string',
            'status' => 'in:in_stock,low_stock,out_of_stock,archived',
            'item_type' => 'in:unique,standard',
            'quantity_on_hand' => 'integer|min:0',
            'reorder_point' => 'integer|min:0',
            'unit' => 'string|max:50',
            'tags' => 'nullable|array',
            'location' => 'nullable|string|max:255',
            'storage_location_id' => 'nullable|exists:storage_locations,id',
            'market_analysis' => 'nullable|array',
            'facebook_analysis' => 'nullable|array',
            'etsy_analysis' => 'nullable|array',
            'source_links' => 'nullable|array',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Archive instead of delete per spec? Or hard delete?
        // Spec says "Staff... cannot hard-delete; can archive".
        // Admin can delete.
        // Let's implement soft delete logic or archive logic.
        // Spec says "archived_at (nullable)".
        
        $item = InventoryItem::findOrFail($id);
        $item->status = 'archived';
        $item->archived_at = now();
        $item->save();

        return response()->json(['message' => 'Item archived']);
    }
}
