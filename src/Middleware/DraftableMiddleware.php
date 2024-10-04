<?php

declare(strict_types=1);

namespace Indra\Revisor\Middleware;

use Closure;
use Illuminate\Http\Request;
use Indra\Revisor\Enums\RevisorMode;
use Indra\Revisor\Facades\Revisor;

class DraftableMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->isDraftRequest($request)) {
            Revisor::setMode(RevisorMode::Draft);
        }

        return $next($request);
    }

    private function isDraftRequest(Request $request): bool
    {
        $referer = $request->headers->get('referer');

        return $request->has('draft') || ($referer && str_contains($referer, '?draft'));
    }
}
