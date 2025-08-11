<?php

namespace Controllers;

use Helpers\ResponseHelper;
use Services\AuthService;

/**
 * Controller responsible for authentication-related endpoints
 */
class AuthController
{
    /**
     * @var AuthService Instance of the authentication service
     */
    private $authService;

    /**
     * AuthController constructor.
     * Initializes the AuthService instance.
     */
    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Handle user login request
     *
     * @param array $data Associative array containing login credentials (e.g. ['email' => '', 'password' => ''])
     * @return void Sends JSON response with login result and HTTP status code
     */
    public function login(array $data): void
    {
        $result = $this->authService->login($data);
        ResponseHelper::jsonResponse($result, $result['statusCode']);
    }

    /**
     * Get user profile details
     *
     * @param int|string $userId The ID of the user whose profile is requested
     * @return void Sends JSON response with user profile data
     */
    public function profile($userId): void
    {
        $result = $this->authService->profile($userId);
        ResponseHelper::jsonResponse($result);
    }
}
