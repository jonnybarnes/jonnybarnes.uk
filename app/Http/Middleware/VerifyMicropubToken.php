<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\MicropubResponses;
use App\Models\MicropubToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMicropubToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawToken = null;

        if ($request->input('access_token')) {
            $rawToken = $request->input('access_token');
        } elseif ($request->bearerToken()) {
            $rawToken = $request->bearerToken();
        }

        if (! $rawToken) {
            return response()->json([
                'response' => 'error',
                'error' => 'unauthorized',
                'error_description' => 'No access token was provided in the request',
            ], 401);
        }

        $token = MicropubToken::where('token_hash', hash('sha256', $rawToken))
            ->whereNull('revoked_at')
            ->first();

        if (! $token) {
            $micropubResponses = new MicropubResponses;

            return $micropubResponses->invalidTokenResponse();
        }

        if ($token->scope === '') {
            $micropubResponses = new MicropubResponses;

            return $micropubResponses->tokenHasNoScopeResponse();
        }

        return $next($request->merge([
            'access_token' => $rawToken,
            'token_data' => [
                'me' => $token->me,
                'scope' => $token->scope,
                'client_id' => $token->client_id,
            ],
        ]));
    }
}
