<?php

namespace Services;

use Models\User;

/**
 * Service class responsible for user-related business logic.
 */
class UserService
{
    /**
     * @var User Instance of User model
     */
    protected $userModel;

    /**
     * UserService constructor.
     * Initializes the User model.
     */
    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Retrieve all users with optional filtering or pagination.
     *
     * @param mixed $req Request parameters (filters, pagination, etc.)
     * @return array List of users with metadata
     */
    public function getAllUsers($req): array
    {
        return $this->userModel->getAllUsers($req);
    }

    /**
     * Create a new user.
     *
     * @param array $data User data for creation
     * @return array Result of create operation
     */
    public function createUser(array $data): array
    {
        return $this->userModel->createUser($data);
    }

    /**
     * Retrieve details of a specific user.
     *
     * @param array $data User identifier data (e.g. ['userId' => 1])
     * @return array User details
     */
    public function getUser(array $data): array
    {
        return $this->userModel->getUser($data);
    }

    /**
     * Update user information.
     *
     * @param array $data User data with updates
     * @return array Result of update operation
     */
    public function updateUser(array $data): array
    {
        return $this->userModel->updateUser($data);
    }

    /**
     * Delete a user.
     *
     * @param array $data User identifier data for deletion
     * @return array Result of delete operation
     */
    public function deleteUser(array $data): array
    {
        return $this->userModel->deleteUser($data);
    }
}
