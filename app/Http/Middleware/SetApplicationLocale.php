<?php

namespace App\Http\Middleware;

use App\Support\LocaleManager;
use Closure;
use Illuminate\Http\Request;

class SetApplicationLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = LocaleManager::resolve($request->user());

        app()->setLocale(LocaleManager::toAppLocale($locale));
        config(['app.locale' => LocaleManager::toAppLocale($locale)]);

        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
