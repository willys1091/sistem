<?php

namespace App\Http\Middleware;

use Closure;

class AdminOrSuperAdmin{
    public function handle($request, Closure $next){
        $user = auth()->user()->user;
        abort_403((!$user->is_superadmin && !$user->hasRole('admin')));

        return $next($request);
    }
}