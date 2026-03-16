<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMemberOrSchoolOnly
{
    /**
     * Dopušta pristup samo korisnicima koji imaju pravo admin/member/school.
     *
     * U suprotnom vraća HTTP 403 kako bi se zaštitile rute namijenjene
     * ovlaštenim korisničkim ulogama.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        if (!$user->imaPravoAdminMemberOrSchool()) {
            abort(403);
        }

        return $next($request);
    }
}
