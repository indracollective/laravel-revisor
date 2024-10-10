<?php

namespace Indra\Revisor\Middleware;

use Closure;
use Illuminate\Http\Request;
use Indra\Revisor\Enums\RevisorContext;
use Indra\Revisor\Facades\Revisor;
use Symfony\Component\HttpFoundation\Response;

class PublishedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Revisor::setContext(RevisorContext::Published);

        return $next($request);
    }
}
