<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            Log::error('Google redirect failed', ['error' => $e->getMessage()]);
            return redirect()->route('login')->with('error', 'Unable to connect to Google. Please try again later.');
        }
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user already exists
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(Str::random(24)), // Random password since Google handles auth
                    'email_verified_at' => now(), // Google verified emails are trusted
                ]);

                // Assign customer role
                $user->assignRole('customer');
                
                Log::info('New user created via Google OAuth', ['user_id' => $user->id, 'email' => $user->email]);
            }

            // Log the user in
            Auth::login($user);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('Google authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')
                ->with('error', 'Google authentication failed. Please try again or use email/password login.');
        }
    }
} 