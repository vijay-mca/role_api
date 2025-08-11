<?php

namespace Controllers;

use Services\RoleService;
use Helpers\ResponseHelper;

/**
 * Controller responsible for role management endpoints
 */
class RoleController
{
    /**
     * @var RoleService Instance of RoleService for business logic
     */
    private $roleService;

    /**
     * RoleController constructor.
     * Initializes RoleService instance.
     */
    public function __construct()
    {
        $this->roleService = new RoleService();
    }

    /**
     * Get all roles with additional roleId information.
     *
     * @param mixed $req Request data (e.g. filters, pagination)
     * @param int|string $roleId Role ID to include in response data
     * @return void Sends JSON response with roles data including roleId
     */
    public function getAllRoles($req, $roleId): void
    {
        $roles = $this->roleService->getAllRoles($req);
        $roles['data']['roleId'] = $roleId;
        ResponseHelper::jsonResponse($roles);
    }

    /**
     * Get roles formatted for dropdown selection.
     *
     * @return void Sends JSON response with role dropdown data
     */
    public function getRoleDropdown(): void
    {
        $result = $this->roleService->getRoleDropdown();
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Create a new role.
     *
     * @param array $data Role data for creation
     * @return void Sends JSON response with result of creation
     */
    public function createRole(array $data): void
    {
        $result = $this->roleService->createRole($data);
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Get details of a specific role.
     *
     * @param array $data Role identifier data (e.g. ['roleId' => 1])
     * @return void Sends JSON response with role details
     */
    public function getRole(array $data): void
    {
        $result = $this->roleService->getRole($data);
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Update an existing role.
     *
     * @param array $data Role data with updated fields
     * @return void Sends JSON response with update result
     */
    public function updateRole(array $data): void
    {
        $result = $this->roleService->updateRole($data);
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Delete a role.
     *
     * @param array $data Role identifier data for deletion
     * @return void Sends JSON response with deletion result
     */
    public function deleteRole(array $data): void
    {
        $result = $this->roleService->deleteRole($data);
        ResponseHelper::jsonResponse($result);
    }
}
