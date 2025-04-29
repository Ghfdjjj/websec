<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FacebookAuthController extends Controller
{
    public function redirectToFacebook()
    {
        try {
            return Socialite::driver('facebook')->redirect();
        } catch (\Exception $e) {
            Log::error('Facebook redirect failed', ['error' => $e->getMessage()]);
            return redirect()->route('login')->with('error', 'Unable to connect to Facebook. Please try again later.');
        }
    }

    public function handleFacebookCallback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();
            
            // Check if user already exists
            $user = User::where('email', $facebookUser->email)->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $facebookUser->name,
                    'email' => $facebookUser->email,
                    'password' => Hash::make(Str::random(24)), // Random password since Facebook handles auth
                    'email_verified_at' => now(), // Facebook verified emails are trusted
                ]);

                // Assign customer role
                $user->assignRole('customer');
                
                Log::info('New user created via Facebook OAuth', ['user_id' => $user->id, 'email' => $user->email]);
            }

            // Log the user in
            Auth::login($user);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('Facebook authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('login')->with('error', 'Facebook authentication failed. Please try again.');
        }
    }
} 