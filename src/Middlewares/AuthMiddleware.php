<?php

namespace Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Helpers\ResponseHelper;
use Helpers\RoleGuardHelper;

class AuthMiddleware
{
    /**
     * Constructor - no initialization needed currently
     */
    public function __construct() {}

    /**
     * Extracts the Bearer token from the HTTP Authorization header.
     * Supports multiple server configurations by checking alternative header sources.
     * 
     * @return string|null Returns the Bearer token string if found, or null if missing.
     */
    public static function getBearerTokenFromHeader()
    {
        $headers = apache_request_headers() ?? [];
        // Attempt to get the Authorization header (case-insensitive)
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader) {
            // Fallback: Some servers set HTTP_AUTHORIZATION in $_SERVER superglobal
            if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            }
        }

        // Extract token from header using regex matching "Bearer <token>"
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Retrieves the module ID from the HTTP 'Module' header.
     * Checks multiple sources for compatibility with different server setups.
     * 
     * @return int Returns the module ID as an integer, or 0 if not present.
     */
    public static function getModule()
    {
        $headers = apache_request_headers() ?? [];
        // Attempt to get the Module header (case-insensitive)
        $module = $headers['Module'] ?? $headers['module'] ?? null;

        if (!$module) {
            // Fallback: Some servers set HTTP_MODULE in $_SERVER superglobal
            if (!empty($_SERVER['HTTP_MODULE'])) {
                $module = $_SERVER['HTTP_MODULE'];
            }
        }

        return (int)$module;
    }

    /**
     * Middleware function to enforce authorization on API requests.
     * Validates the JWT token from Authorization header, checks for module access,
     * and returns the decoded token payload if successful.
     * 
     * On failure, sends a JSON error response and terminates execution.
     * 
     * @param string $requestUri Optional URI string to modify module permission check behavior.
     * 
     * @return object Decoded JWT token payload on successful authorization.
     */
    public static function requireAuth($requestUri = '')
    {
        // Retrieve the bearer token from the request headers
        $token = self::getBearerTokenFromHeader();

        if (!$token) {
            // No token found - respond with unauthorized error
            ResponseHelper::jsonResponse([
                "status" => "error",
                "statusCode" => 500,
                "message" => "Unauthorized, token missing",
            ]);
            exit;
        }

        try {
            // Decode and verify the JWT token using secret key and HS256 algorithm
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            // Extract requested module ID from headers
            $moduleId = self::getModule();

            // If $requestUri is empty and moduleId is not zero, check if user has access to the module
            if (empty($requestUri) && $moduleId !== 0) {
                $roleModuleIds = $decoded->data->roleModules ?? [];
                // Check if the decoded token's roleModules includes access to this module
                if (!RoleGuardHelper::hasModuleAccess($moduleId, $roleModuleIds)) {
                    // Access denied - user lacks permission for requested module
                    ResponseHelper::jsonResponse([
                        "status" => "error",
                        "statusCode" => 403,
                        "message" => "Access denied: no module permission",
                        "error" => "Access denied: no module permission"
                    ]);
                    exit;
                }
            }

            // Authorization successful, return decoded token data for further use
            return $decoded;
        } catch (Exception $e) {
            // Token invalid, expired, or decode error - respond with error details
            ResponseHelper::jsonResponse([
                "status" => "error",
                "statusCode" => 500,
                "message" => "Invalid or expired token",
                "error" => $e->getMessage()
            ]);
            exit;
        }
    }
}
