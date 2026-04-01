<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller for managing inventory purchases from vendors.
 */
class PurchaseController extends Controller
{
    /**
     * Display a listing of purchases.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['vendor', 'inventoryItem']);

        if ($request->has('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        return $query->latest()->paginate(25);
    }

    /**
     * Store a newly created purchase and update inventory.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'vendor_id' => 'required|exists:vendors,id',
            'quantity_purchased' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'shipping_cost' => 'numeric|min:0',
            'tax_cost' => 'numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'purchased_at' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $purchase = new Purchase($validated);
            $purchase->created_by_user_id = $request->user()->id;
            $purchase->save();

            // Update inventory quantity
            $item = InventoryItem::findOrFail($validated['inventory_item_id']);
            $item->increment('quantity_on_hand', $validated['quantity_purchased']);

            return response()->json($purchase, 201);
        });
    }

    /**
     * Display the specified purchase.
     *
     * @param  string  $id
     * @return \App\Models\Purchase
     */
    public function show(string $id)
    {
        return Purchase::with(['vendor', 'inventoryItem', 'creator'])->findOrFail($id);
    }

    /**
     * Update the specified purchase and adjust inventory quantity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $purchase = Purchase::findOrFail($id);

        $validated = $request->validate([
            'vendor_id' => 'sometimes|exists:vendors,id',
            'quantity_purchased' => 'sometimes|integer|min:1',
            'unit_cost' => 'sometimes|numeric|min:0',
            'shipping_cost' => 'numeric|min:0',
            'tax_cost' => 'numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'purchased_at' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($purchase, $validated) {
            $oldQty = $purchase->quantity_purchased;
            
            $purchase->update($validated);

            if (isset($validated['quantity_purchased']) && $validated['quantity_purchased'] != $oldQty) {
                $diff = $validated['quantity_purchased'] - $oldQty;
                $item = InventoryItem::findOrFail($purchase->inventory_item_id);
                $item->increment('quantity_on_hand', $diff);
            }

            return response()->json($purchase);
        });
    }

    /**
     * Remove the specified purchase and revert inventory quantity.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        return DB::transaction(function () use ($id) {
            $purchase = Purchase::findOrFail($id);
            
            // Revert inventory quantity
            $item = InventoryItem::findOrFail($purchase->inventory_item_id);
            $item->decrement('quantity_on_hand', $purchase->quantity_purchased);

            $purchase->delete();

            return response()->json(null, 204);
        });
    }
}
