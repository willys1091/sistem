<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyPackage{
    public function handle(Request $request, Closure $next): Response{
        $allowedRoutes = [
            'employees.index',
            'employees.edit',
            'employees.update',
            'employees.destroy',
            'employees.apply_quick_action',
            'import.process.progress',
            'import.process.exception',
            'profile.dark_theme',
        ];

        if (user() && user()->company_id && !$request->routeIs($allowedRoutes)) {
            $isAllowedInCurrentPackage = checkCompanyPackageIsValid(user()->company_id);
            if (!$isAllowedInCurrentPackage) {
                if(in_array('admin', user_roles())) {
                    return redirect()->route('billing.index');
                }
                return redirect()->route('superadmin.notify.admin');
            }
        }

        $notAllowedRoutes = [
            'employees.create',
            'employees.store',
            'employees.import',
            'employees.import.store',
            'employees.send_invite',
            'employees.create_link',
        ];

        if (user() && user()->company_id && $request->routeIs($notAllowedRoutes)) {
            abort_403(!checkCompanyCanAddMoreEmployees(user()->company_id));
        }
        return $next($request);
    }
}