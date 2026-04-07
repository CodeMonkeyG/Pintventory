<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = User::where('google_id', $googleUser->id)->first();

            if (!$user) {
                $user = User::where('email', $googleUser->email)->first();
                if ($user) {
                    $user->google_id = $googleUser->id;
                    $user->avatar = $googleUser->avatar;
                    $user->save();
                } else {
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'avatar' => $googleUser->avatar,
                        'google_id' => $googleUser->id,
                        'password' => Hash::make(Str::random(24)),
                        'role' => 'staff',
                        'email_verified_at' => now(),
                    ]);
                }
            }

            // Ensure user has at least one workspace
            if (!$user->current_workspace_id) {
                $workspace = $user->workspaces()->first();
                if (!$workspace) {
                    $workspace = Workspace::create([
                        'name' => 'Personal Workspace',
                        'created_by_user_id' => $user->id,
                    ]);
                    $workspace->users()->attach($user->id, ['role' => 'owner']);
                }
                $user->current_workspace_id = $workspace->id;
                $user->save();
            }

            Auth::login($user, true);
            return redirect('/');

        } catch (\Exception $e) {
            \Log::error('Google OAuth callback failed: ' . $e->getMessage());
            return redirect('/login?error=Google login failed');
        }
    }
}
