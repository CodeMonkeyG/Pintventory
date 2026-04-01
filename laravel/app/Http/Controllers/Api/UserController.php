<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for managing user profiles and preferences.
 */
class UserController extends Controller
{
    /**
     * Display a listing of users (Admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Pagination\LengthAwarePaginator
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
     * Display the authenticated user's information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\User
     */
    public function show(Request $request)
    {
        return $request->user();
    }

    /**
     * Update the authenticated user's profile and preferences.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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

    /**
     * Log out the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
