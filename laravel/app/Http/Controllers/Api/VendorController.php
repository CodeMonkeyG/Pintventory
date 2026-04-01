<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

/**
 * Controller for managing vendors and tracking procurement.
 */
class VendorController extends Controller
{
    /**
     * Display a listing of vendors with spend metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $query = Vendor::query()->withSpendMetrics();

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->boolean('is_preferred')) {
            $query->where('is_preferred', true);
        }

        $sortField = $request->input('sort_by', 'updated_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($request->input('per_page', 25));
    }

    /**
     * Store a newly created vendor in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_preferred' => 'boolean',
        ]);

        $vendor = new Vendor($validated);
        $vendor->created_by_user_id = $request->user()->id;
        $vendor->save();

        return response()->json($vendor, 201);
    }

    /**
     * Display the specified vendor with purchase history.
     *
     * @param  string  $id
     * @return \App\Models\Vendor
     */
    public function show(string $id)
    {
        return Vendor::with(['purchases.inventoryItem'])->findOrFail($id);
    }

    /**
     * Update the specified vendor in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_preferred' => 'boolean',
        ]);

        $vendor->update($validated);

        return response()->json($vendor);
    }

    /**
     * Remove the specified vendor from storage (Soft Delete).
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted']);
    }
}
