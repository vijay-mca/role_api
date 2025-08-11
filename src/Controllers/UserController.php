<?php

namespace Controllers;

use Services\UserService;
use Helpers\ResponseHelper;

/**
 * Controller responsible for user management endpoints
 */
class UserController
{
    /**
     * @var UserService Instance of UserService for business logic
     */
    private $userService;

    /**
     * UserController constructor.
     * Initializes UserService instance.
     */
    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Get list of users with optional role filter.
     *
     * @param mixed $req Request data (e.g. filters, pagination)
     * @param int|string $roleId Role ID to include in response data
     * @return void Sends JSON response with users data including role info
     */
    public function getUsers($req, $roleId): void
    {
        $users = $this->userService->getAllUsers($req);
        $users['data']['role'] = $roleId;
        ResponseHelper::jsonResponse($users);
    }

    /**
     * Create a new user.
     *
     * @param array $data User data for creation
     * @return void Sends JSON response with result of creation
     */
    public function createUser(array $data): void
    {
        $result = $this->userService->createUser($data);
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Get details of a specific user.
     *
     * @param array $data User identifier data (e.g. ['userId' => 1])
     * @return void Sends JSON response with user details
     */
    public function getUser(array $data): void
    {
        $result = $this->userService->getUser($data);
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Update an existing user.
     *
     * @param array $data User data with updated fields
     * @return void Sends JSON response with update result
     */
    public function updateUser(array $data): void
    {
        $result = $this->userService->updateUser($data);
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Delete a user.
     *
     * @param array $data User identifier data for deletion
     * @return void Sends JSON response with deletion result
     */
    public function deleteUser(array $data): void
    {
        $result = $this->userService->deleteUser($data);
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Verify a user.
     *
     * @param array $data Verification data related to user
     * @return void Sends JSON response confirming verification success
     */
    public function verifyUser($data): void
    {
        ResponseHelper::jsonResponse([
            'statusCode' => 200,
            'status' => 'success',
            'message' => 'User verified successfully',
            'data' => $data
        ]);
    }
}
