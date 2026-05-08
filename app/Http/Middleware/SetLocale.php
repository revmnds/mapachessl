<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['es', 'en', 'nl'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'es'));

        if (!in_array($locale, self::SUPPORTED_LOCALES)) {
            $locale = config('app.locale', 'es');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
