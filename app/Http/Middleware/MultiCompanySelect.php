<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Scopes\ActiveScope;
use App\Scopes\CompanyScope;
use Closure;
use Illuminate\Http\Request;

class MultiCompanySelect{
    public function handle(Request $request, Closure $next){
        if (session()->get('user_company_count') > 1 && !session()->has('multi_company_selected')) {
            return redirect(route('superadmin.superadmin.workspaces'));
        }

//        if (!session()->has('impersonate') && !session()->has('stop_impersonate')) {
//
//
//            try {
//                if (auth()->check()) {
//                    auth()->user()->user->update(['last_login' => now()]);
//                }
//
//
//                if (company()) {
//                    $company = company();
//                    $company->last_login = now();
//                    /* @phpstan-ignore-line */
//                    $company->saveQuietly();
//                }
//            } catch (\Exception $e) {
//
//            }

//        }
        return $next($request);
    }
}