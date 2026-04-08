<?php



namespace App\Modules\Tenant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->tenant_id) {
            abort(403, 'No tenant assigned to this user.');
        }

        return $next($request);
    }
}
