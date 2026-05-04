<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialiteUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProvideCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect('/admin/login')->with('error', 'Login failed. Please try again.');
        }

        $authUser = $this->findOrCreateUser($socialUser, $provider);

        Auth::login($authUser, true);

        return redirect()->route('filament.admin.pages.dashboard');
    }

    public function findOrCreateUser($socialUser, $provider)
    {
        $socialAccount = SocialiteUser::where('provider_id', $socialUser->getId())
            ->where('provider', $provider)
            ->first();

        if ($socialAccount) {
            return $socialAccount->user;
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'email_verified_at' => Carbon::now(),
            ]);
        }

        $user->socialiteUsers()->create([
            'provider_id' => $socialUser->getId(),
            'provider' => $provider,
        ]);

        return $user;
    }
}