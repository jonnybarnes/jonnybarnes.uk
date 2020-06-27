<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class MicropubResponses
{
    /**
     * Generate a response to be returned when the token has insufficient scope.
     *
     * @return JsonResponse
     */
    public function insufficientScopeResponse(): JsonResponse
    {
        return response()->json([
            'response' => 'error',
            'error' => 'insufficient_scope',
            'error_description' => 'The token’s scope does not have the necessary requirements.',
        ], 401);
    }

    /**
     * Generate a response to be returned when the token is invalid.
     *
     * @return JsonResponse
     */
    public function invalidTokenResponse(): JsonResponse
    {
        return response()->json([
            'response' => 'error',
            'error' => 'invalid_token',
            'error_description' => 'The provided token did not pass validation',
        ], 400);
    }

    /**
     * Generate a response to be returned when the token has no scope.
     *
     * @return JsonResponse
     */
    public function tokenHasNoScopeResponse(): JsonResponse
    {
        return response()->json([
            'response' => 'error',
            'error' => 'invalid_request',
            'error_description' => 'The provided token has no scopes',
        ], 400);
    }
}
