<?php

namespace App\Http\Middleware;

use App\Models\GlobalSetting;
use Closure;

class DisableFrontend{
    public function handle($request, Closure $next){
        $global = global_setting();

        if ($global->frontend_disable && request()->route()->getName() != 'front.signup.index' && !request()->ajax()) {
            return redirect(route('login'));
        }
        return $next($request);
    }
}