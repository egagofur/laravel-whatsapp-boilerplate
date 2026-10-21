<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppBasicAuth
{
    /**
     * Protect admin / API routes with HTTP Basic Auth.
     * Fails closed when credentials are not configured.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = (string) config('whatsapp.basic_auth.user', '');
        $authPassword = (string) config('whatsapp.basic_auth.password', '');

        if ($authUser === '' || $authPassword === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Server authentication is not configured',
            ], 503);
        }

        if ($request->getUser() !== $authUser || $request->getPassword() !== $authPassword) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="WhatsApp Admin Area"',
            ]);
        }

        return $next($request);
    }
}
