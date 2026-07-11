<?php

use Illuminate\Http\Response;

if (! function_exists('respondWithData')) {
    function respondWithData($data, $message = null)
    {
        if ($message) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        return response()->json(
            $payload,
            200,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );
    }
}

if (! function_exists('respondWithMessage')) {
    function respondWithMessage(string $message, int $statusCode = Response::HTTP_OK)
    {
        return response()->json([
            'message' => $message,
        ], $statusCode);
    }
}

if (! function_exists('respondError')) {
    function respondError($error, $statusCode = Response::HTTP_BAD_REQUEST)
    {
        return response()->json(
            [
                'message' => $error,
            ],
            $statusCode,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );
    }
}
