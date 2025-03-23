<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class CheckAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get Authorization header
        $authorizationHeader = $request->header('Authorization');

        // Check if it contains 'Bearer' token
        if ($authorizationHeader && preg_match('/Bearer\s+(\S+)/', $authorizationHeader, $matches)) {
            // Extract the token
            $bearerToken = $matches[1];

            // Verify the token with the user service
            $response = Http::withHeaders([
                'Authorization' => "Bearer $bearerToken",
                'Accept' => 'application/json',
            ])->get(config('app.user_service_url') . '/api/user');

            // If the response is not 200, reject the request
            if ($response->failed()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Optionally, you can store user data from the response
            $userData = $response->json();
            // $request->merge(['authenticated_user' => $userData]);

            // Continue the request
            return $next($request);
        }

        // If no Bearer token is found, return an error response
        return response()->json(['error' => 'Bearer token missing'], 401);
    }
}
