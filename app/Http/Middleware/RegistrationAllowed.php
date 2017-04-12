<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

class RegistrationAllowed
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
        $max_users = \Config::get('app.max_users');

        if ($max_users && (User::count() >= $max_users)) {
            return redirect('/');
        }

        return $next($request);
    }
}
