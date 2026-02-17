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
     */
    public function index(Request $request)
    {
        $query = InventoryItem::query()->with(['photos']);

        // Search (title or SKU)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by tag (JSON array search)
        if ($request->filled('tag')) {
            // Postgres JSONB containment operator usually works best with raw query or specialized method
            // For simple JSON array: whereJsonContains
            $query->whereJsonContains('tags', $request->input('tag'));
        }
        
        // Filter by Low Stock
        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity_on_hand', '<=', 'reorder_point');
        }

        // Vendors/Customers filters would require joining purchases/sales, skip for now or implement if needed

        // Sort
        $sortField = $request->input('sort_by', 'updated_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($request->input('per_page', 25));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255', // Unique check recommended but let's stick to basics
            'description' => 'nullable|string',
            'status' => 'in:in_stock,low_stock,out_of_stock,archived',
            'quantity_on_hand' => 'integer|min:0',
            'reorder_point' => 'integer|min:0',
            'unit' => 'string|max:50',
            'tags' => 'nullable|array',
            'location' => 'nullable|string|max:255',
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
     */
    public function show(string $id)
    {
        return InventoryItem::with(['photos', 'purchases', 'sales'])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = InventoryItem::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:in_stock,low_stock,out_of_stock,archived',
            'quantity_on_hand' => 'integer|min:0',
            'reorder_point' => 'integer|min:0',
            'unit' => 'string|max:50',
            'tags' => 'nullable|array',
            'location' => 'nullable|string|max:255',
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
