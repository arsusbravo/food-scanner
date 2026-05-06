<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['en', 'nl', 'de', 'fr', 'es'];
        $locale    = $request->cookie('locale', 'en');
        App::setLocale(in_array($locale, $supported) ? $locale : 'en');

        return $next($request);
    }
}
