<?php

namespace Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Middlewares\AuthMiddleware;

class JwtHelper
{
    /**
     * Decode JWT token extracted from the Authorization header.
     * 
     * Retrieves the bearer token using `AuthMiddleware::getBearerTokenFromHeader()`, 
     * then decodes and verifies it using the secret key from environment variables.
     * 
     * @throws \Exception Throws if the token is invalid or missing, but here it returns a structured array instead.
     * 
     * @return array Returns an associative array with:
     *  - 'status' (string): 'success' or 'error' indicating the decode result,
     *  - 'statusCode' (int): HTTP status code corresponding to the result,
     *  - 'data' (array|null): Contains user info ('id', 'email', 'role', 'modules') if successful,
     *  - 'message' (string): Error or success message,
     *  - 'error' (string|null): Detailed error message if decoding failed.
     */
    public static function decodeToken()
    {
        $jwt = AuthMiddleware::getBearerTokenFromHeader();

        // Retrieve the JWT secret key from environment
        $secretKey = $_ENV['JWT_SECRET'] ?? '';
        if (!$secretKey) {
            return [
                "status" => "error",
                "statusCode" => 500,
                "message" => "JWT secret key not configured."
            ];
        }

        try {
            // Decode and verify the JWT token signature and payload
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));

            return [
                "status" => "success",
                "statusCode" => 200,
                "data" => [
                    'id'      => $decoded->sub ?? null,           // User ID from JWT 'sub' claim
                    'email'   => $decoded->data->email ?? null,   // User email
                    'role'    => $decoded->data->role ?? null,    // User role
                    'modules' => $decoded->data->modules ?? []    // User modules/permissions
                ]
            ];
        } catch (\Exception $e) {
            // Return structured error if token is invalid or expired
            return [
                "status" => "error",
                "statusCode" => 401,
                "message" => "Invalid or expired token.",
                "error" => $e->getMessage()
            ];
        }
    }
}
