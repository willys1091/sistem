<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware{
    protected function redirectTo($request){
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, Closure $next, ...$guards){
        if (user()) {
            $isActive = cache()->rememberForever('user_is_active_' . user()->id, function () {
                return User::where('id', user()->id)
                    ->where('status', 'active')
                    ->exists();
            });

            if (!$isActive) {
                auth()->logout();
                session()->flush();

                return redirect()->route('login');
            }
        }
        $this->authenticate($request, $guards);
        return $next($request);
    }
}