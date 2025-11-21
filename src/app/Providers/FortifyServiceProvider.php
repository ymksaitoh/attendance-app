<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use App\Http\Requests\CustomLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FortifyLoginRequest::class, CustomLoginRequest::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::registerView(fn () => view('auth.register'));
        Fortify::loginView(fn () => view('auth.login'));

        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                $request->user()->sendEmailVerificationNotification();
                return redirect()->route('verification.notice');
            }
        });

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                $user = $request->user();
                if ($user && !$user->hasVerifiedEmail()) {
                    return redirect()->route('verification.notice');
                }
                return redirect()->route('attendances.create');
            }
        });

        Fortify::authenticateUsing(function ($request) {

            $user = \App\Models\User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません'],
                ]);
            }

            $routeName = $request->route() ? $request->route()->getName() : null;

            if ($routeName === 'login' && $user->role !== 'user') {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません']
                ]);
            }

            if ($routeName === 'admin.login' && $user->role !== 'admin') {
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません']
                ]);
            }

            return $user;
        });

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                $user = $request->user();

                if (!$user->hasVerifiedEmail()) {
                    return redirect()->route('verification.notice');
                }

                if ($user->role === 'admin') {
                    return redirect()->route('admin.attendances.list');
                }

                return redirect()->route('attendances.create');
            }
        });

        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {

            return redirect('/login');
        }
        });
    }
}
