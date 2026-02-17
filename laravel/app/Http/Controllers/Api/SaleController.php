<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'inventoryItem']);

        if ($request->has('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        return $query->latest()->paginate(25);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'customer_id' => 'required|exists:customers,id',
            'quantity_sold' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'numeric|min:0',
            'tax' => 'numeric|min:0',
            'payment_status' => 'in:paid,unpaid,partial',
            'notes' => 'nullable|string',
            'sold_at' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $item = InventoryItem::findOrFail($validated['inventory_item_id']);

            if ($item->quantity_on_hand < $validated['quantity_sold']) {
                return response()->json([
                    'message' => 'Insufficient stock for this item.',
                    'quantity_on_hand' => $item->quantity_on_hand
                ], 422);
            }

            $sale = new Sale($validated);
            $sale->created_by_user_id = $request->user()->id;
            $sale->save();

            // Update inventory quantity
            $item->decrement('quantity_on_hand', $validated['quantity_sold']);

            return response()->json($sale, 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Sale::with(['customer', 'inventoryItem', 'creator'])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sale = Sale::findOrFail($id);
        
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'quantity_sold' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
            'discount' => 'numeric|min:0',
            'tax' => 'numeric|min:0',
            'payment_status' => 'in:paid,unpaid,partial',
            'notes' => 'nullable|string',
            'sold_at' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($sale, $validated) {
            $oldQty = $sale->quantity_sold;

            // Handle quantity change logic
            if (isset($validated['quantity_sold']) && $validated['quantity_sold'] != $oldQty) {
                $diff = $validated['quantity_sold'] - $oldQty;
                $item = InventoryItem::findOrFail($sale->inventory_item_id);

                if ($diff > 0 && $item->quantity_on_hand < $diff) {
                     return response()->json([
                        'message' => 'Insufficient stock to increase sale quantity.',
                        'quantity_on_hand' => $item->quantity_on_hand
                    ], 422);
                }
                
                $item->decrement('quantity_on_hand', $diff);
            }

            $sale->update($validated);

            return response()->json($sale);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return DB::transaction(function () use ($id) {
            $sale = Sale::findOrFail($id);
            
            // Revert inventory quantity (restock)
            $item = InventoryItem::findOrFail($sale->inventory_item_id);
            $item->increment('quantity_on_hand', $sale->quantity_sold);

            $sale->delete();

            return response()->json(null, 204);
        });
    }
}
