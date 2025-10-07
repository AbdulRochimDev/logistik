<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication required.');
        }

        $roleList = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $candidate) {
                $candidate = trim($candidate);
                if ($candidate !== '') {
                    $roleList[] = $candidate;
                }
            }
        }

        if ($roleList === []) {
            return $next($request);
        }

        if (! $user->hasAnyRole($roleList)) {
            abort(Response::HTTP_FORBIDDEN, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
