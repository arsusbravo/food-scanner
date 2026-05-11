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
        $supported = ['en', 'nl', 'de', 'fr', 'es', 'zh-TW', 'zh-CN', 'tr'];

        if ($request->hasCookie('locale') && in_array($request->cookie('locale'), $supported)) {
            $locale = $request->cookie('locale');
        } else {
            $locale = $request->getPreferredLanguage($supported) ?? 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
