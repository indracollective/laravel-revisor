<?php

namespace Indra\Revisor\Middleware;

use Closure;
use Illuminate\Http\Request;
use Indra\Revisor\Facades\Revisor;
use Symfony\Component\HttpFoundation\Response;

class DraftMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Revisor::draftContext();

        return $next($request);
    }
}
