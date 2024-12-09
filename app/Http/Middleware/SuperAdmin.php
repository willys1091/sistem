<?php

namespace App\Http\Middleware;

use Closure;

class SuperAdmin{
    public function handle($request, Closure $next){
        $user = user();
        abort_403(!$user->is_superadmin);

        return $next($request);
    }
}