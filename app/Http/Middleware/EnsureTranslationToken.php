<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTranslationToken{
    public function handle(Request $request, Closure $next): Response{
        if (isWorksuite()) {
            abort_403(!(user()->permission('manage_language_setting') == 'all'));
        }

        if (isWorksuiteSaas() ) {
            if (!(user() instanceof \App\Models\User)) {
                session(['user' => auth()->user()->user]);
            }

            abort_403(!(user()->permission('manage_superadmin_language_settings') == 'all'));
        }
        return $next($request);
    }
}