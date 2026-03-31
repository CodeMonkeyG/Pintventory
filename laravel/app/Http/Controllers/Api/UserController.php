<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Admin only - enforced by policy or middleware
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'owner') {
             return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return User::paginate($request->input('per_page', 25));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        return $request->user();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'preferences' => 'nullable|array',
            'preferences.currency' => 'nullable|string|size:3',
            'preferences.date_format' => 'nullable|string',
            'preferences.tax_handling' => 'nullable|string',
            'preferences.theme' => 'nullable|string',
            'preferences.low_stock_notification' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['preferences'])) {
            // Merge with existing preferences
            $currentPreferences = $user->preferences ?? [];
            $user->preferences = array_merge($currentPreferences, $validated['preferences']);
        }

        $user->save();

        return response()->json($user);
    }
}
