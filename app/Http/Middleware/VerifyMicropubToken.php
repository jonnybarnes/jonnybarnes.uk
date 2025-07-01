<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\MicropubResponses;
use Closure;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
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

        try {
            $tokenData = $this->validateToken($rawToken);
        } catch (RequiredConstraintsViolated|InvalidTokenStructure|CannotDecodeContent) {
            $micropubResponses = new MicropubResponses;

            return $micropubResponses->invalidTokenResponse();
        }

        if ($tokenData->claims()->has('scope') === false) {
            $micropubResponses = new MicropubResponses;

            return $micropubResponses->tokenHasNoScopeResponse();
        }

        return $next($request->merge([
            'access_token' => $rawToken,
            'token_data' => [
                'me' => $tokenData->claims()->get('me'),
                'scope' => $tokenData->claims()->get('scope'),
                'client_id' => $tokenData->claims()->get('client_id'),
            ],
        ]));
    }

    /**
     * Check the token signature is valid.
     */
    private function validateToken(string $bearerToken): Token
    {
        $config = resolve(Configuration::class);

        $token = $config->parser()->parse($bearerToken);

        $constraints = $config->validationConstraints();

        $config->validator()->assert($token, ...$constraints);

        return $token;
    }
}
