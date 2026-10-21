<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class N8nBasicAuth
{
    /**
     * Protect n8n integration routes with HTTP Basic Auth.
     * Fails closed when credentials are not configured.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = (string) config('whatsapp.n8n_auth.user', '');
        $authPassword = (string) config('whatsapp.n8n_auth.password', '');

        if ($authUser === '' || $authPassword === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'n8n authentication is not configured',
            ], 503);
        }

        if ($request->getUser() !== $authUser || $request->getPassword() !== $authPassword) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid n8n Basic Auth credentials',
            ], 401, [
                'WWW-Authenticate' => 'Basic realm="n8n Integration API"',
            ]);
        }

        return $next($request);
    }
}
