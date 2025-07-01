<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class setLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->getLocale($request);

        if ($this->isValidLocale($locale)) {

            // Set the locale
            App::setLocale($locale);

            // Set the locale in the session
            Session::put('locale', $locale);

            // Set the locale in the cookie
            Cookie::queue('locale', $locale, 525600);

            // Save user preference if authenticated
            if (Auth::check()) {
                Auth::user()->profile()->update(['locale' => $locale]);
            }
        }

        return $next($request);
    }
    private function getLocale(Request $request)
    {
        // 1. Session
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 2. User preference
        if (Auth::check() && Auth::user()->locale) {
            return Auth::user()->locale;
        }

        // 3. Browser preference
        if ($request->header('Accept-Language')) {
            $browserLocale = substr($request->header('Accept-Language'), 0, 2);
            if ($this->isValidLocale($browserLocale)) {
                return $browserLocale;
            }
        }

        // 4. Cookie
        if ($request->cookie('locale')) {
            return $request->cookie('locale');
        }

        // 5. Default
        return config('app.locale');
    }

    private function isValidLocale($locale)
    {
        return array_key_exists($locale, config('app.available_locales'));
    }
}
