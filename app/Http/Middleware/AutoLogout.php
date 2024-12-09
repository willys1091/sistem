<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoLogout{
    public function handle(Request $request, Closure $next): Response{
        if (user() && user()->company_id) {
            if (checkActiveCompany(user()->company_id)) {
                auth()->logout();
                session()->flush();
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}