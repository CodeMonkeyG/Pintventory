<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * Controller for handling Google OAuth 2.0 authentication.
 */
class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirect()
    {
        error_log('Redirecting to Google for authentication');
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and log the user in.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback()
    {
        error_log('Callback to Google for authentication');
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('google_id', $googleUser->id)->first();

            if (!$user) {
                // Check for existing email to prevent duplicate accounts
                $user = User::where('email', $googleUser->email)->first();
                if ($user) {
                    // Update existing user with google_id
                    $user->google_id = $googleUser->id;
                    $user->avatar = $googleUser->avatar;
                    $user->save();
                } else {
                    // Create a new user
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'avatar' => $googleUser->avatar,
                        'google_id' => $googleUser->id,
                        'password' => Hash::make(Str::random(24)),
                        'role' => 'staff', // Default role
                        'email_verified_at' => now(),
                    ]);
                }
            }

            // Log in the user
            Auth::login($user, true); // true = remember me

            // Redirect to frontend
            return redirect('/');

        } catch (\Exception $e) {
            \Log::error('Google OAuth callback failed: ' . $e->getMessage());
            return redirect('/login?error=Google login failed');
        }
    }
}
