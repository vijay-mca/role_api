<?php

namespace Services;

use Helpers\CryptoHelper;
use Models\Auth;
use Firebase\JWT\JWT;

/**
 * Service layer for authentication operations including login and profile retrieval.
 */
class AuthService
{
    /**
     * @var Auth Instance of the Auth model for DB operations
     */
    protected $authModel;

    /**
     * @var string Initialization vector used for encryption (if applicable)
     */
    private $iv;

    /**
     * AuthService constructor.
     * Initializes Auth model and generates encryption IV.
     */
    public function __construct()
    {
        $this->authModel = new Auth();
        $this->iv = CryptoHelper::generateIv();
    }

    /**
     * Attempt to login a user or admin.
     *
     * @param array $data Associative array containing 'email', 'password', and 'type' keys
     * @return array Response array with status, statusCode, message, and user/token data on success
     */
    public function login(array $data): array
    {
        if ($data['type'] === '/admin') {
            $user = $this->authModel->adminLogin($data);
        } else {
            $user = $this->authModel->userLogin($data);
        }

        if ($user['statusCode'] !== 200 || empty($user['data']['user'])) {
            return [
                "status" => "invalid_user",
                "statusCode" => 401,
                "message" => "Invalid email or password.",
            ];
        }

        if (password_verify($data['password'], $user['data']['user']->password)) {
            // Clear password from user data before response
            $user['data']['user']->password = null;

            $userId = $user['data']['user']->userId;
            $now = time();
            $exp = $now + (int)($_ENV['JWT_EXP'] ?? 3600);

            // Prepare modules array from concatenated string
            $modulesString = $user['data']['user']->modules;
            $modulesArray = [];

            if (!empty($modulesString)) {
                $items = explode(',', $modulesString);
                $routePrefix = ((int)$user['data']['user']->role_id === 1) ? '/admin' : '/app';
                foreach ($items as $item) {
                    list($id, $name, $routeSlug) = explode(':', $item);
                    $modulesArray[] = [
                        'id' => (int)$id,
                        'name' => $name,
                        'routeSlug' => $routePrefix . '/' . $routeSlug,
                    ];
                }
            }

            // JWT payload
            $payload = [
                'iat' => $now,
                'nbf' => $now,
                'exp' => $exp,
                'iss' => $_ENV['JWT_ISS'] ?? null,
                'aud' => $_ENV['JWT_AUD'] ?? null,
                'sub' => $userId,
                'data' => [
                    'id' => $userId,
                    'email' => $user['data']['user']->email ?? null,
                    'role' => $user['data']['user']->role_id ?? null,
                    'modules' => $modulesArray,
                    'roleModules' => $user['data']['user']->roleModules ?? [],
                    'roles' => $user['data']['user']->roles ?? [],
                ],
            ];

            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            return [
                "status" => "success",
                "statusCode" => 200,
                "message" => "Login successful.",
                "data" => [
                    "user" => $user['data']['user'],
                    "token" => $jwt,
                    "modules" => $modulesArray,
                ],
            ];
        } else {
            return [
                "status" => "invalid_user",
                "statusCode" => 401,
                "message" => "Invalid email or password.",
            ];
        }
    }

    /**
     * Retrieve user profile information.
     *
     * @param int|string $userId User ID to fetch profile for
     * @return array Profile data array or error response
     */
    public function profile($userId): array
    {
        return $this->authModel->profile($userId);
    }
}
