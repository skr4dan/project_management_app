<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse as LaravelJsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JsonResponse
{
    /**
     * Create a success response with data
     */
    public static function success(mixed $data, string $message, int $status = Response::HTTP_OK): LaravelJsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Create a success response without data
     */
    public static function successMessage(string $message, int $status = Response::HTTP_OK): LaravelJsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $status);
    }

    /**
     * Create a success response with pagination data
     */
    public static function successPaginated(mixed $data, mixed $pagination, string $message, int $status = Response::HTTP_OK): LaravelJsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
            'message' => $message,
        ], $status);
    }

    /**
     * Create a created response with data
     */
    public static function created(mixed $data, string $message): LaravelJsonResponse
    {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Create an error response
     */
    public static function error(string $message, int $status = Response::HTTP_BAD_REQUEST): LaravelJsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Create a not found response
     */
    public static function notFound(string $message = 'Resource not found'): LaravelJsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Create an unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): LaravelJsonResponse
    {
        return self::error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Create a forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): LaravelJsonResponse
    {
        return self::error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Create a bad request response
     */
    public static function badRequest(string $message = 'Bad request'): LaravelJsonResponse
    {
        return self::error($message, Response::HTTP_BAD_REQUEST);
    }

    /**
     * Create an internal server error response
     */
    public static function internalServerError(string $message = 'Internal server error'): LaravelJsonResponse
    {
        return self::error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
