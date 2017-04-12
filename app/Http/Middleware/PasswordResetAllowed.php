<?php

namespace App\Http\Middleware;

use Closure;

class PasswordResetAllowed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!\Config::get('app.allow_password_resets')) {
            return redirect('/');
        }

        return $next($request);
    }
}
