<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowWebDavMethods
{
   public function handle(Request $request, Closure $next)
    {
        // Tu lógica de OPTIONS
        if ($request->method() === 'OPTIONS') {
            return response('', 200, [
                'Allow' => 'OPTIONS, GET, HEAD, PUT, POST, DELETE, PROPFIND, MKCOL, MOVE, COPY, LOCK, UNLOCK',
                'DAV'   => '1,2',
                'MS-Author-Via' => 'DAV',
            ]);
        }

        return $next($request);
    }
}
