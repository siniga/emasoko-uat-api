<?php

namespace App\Http\Controllers\Api\ExternalApp\Concerns;

trait RespondsWithApi
{
    protected function successResponse(string $message, array $data = [], int $status = 200)
    {
        return response()->json([
            'success' => true,
            'response_code' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorResponse(string $message, $errors = null, int $status = 400, array $data = [])
    {
        $payload = [
            'success' => false,
            'response_code' => $status,
            'message' => $message,
            'data' => $data,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
