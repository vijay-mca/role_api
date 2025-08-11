<?php

namespace Services;

use Models\Role;

/**
 * Service class handling business logic related to roles.
 */
class RoleService
{
    /**
     * @var Role Instance of Role model
     */
    protected $roleModel;

    /**
     * RoleService constructor.
     * Initializes Role model.
     */
    public function __construct()
    {
        $this->roleModel = new Role();
    }

    /**
     * Retrieve all roles based on request parameters.
     *
     * @param mixed $req Request data (filters, pagination, etc.)
     * @return array List of roles with metadata
     */
    public function getAllRoles($req): array
    {
        return $this->roleModel->getAllRoles($req);
    }

    /**
     * Retrieve roles formatted for dropdown selection.
     *
     * @return array List of roles for dropdown
     */
    public function getRoleDropdown(): array
    {
        return $this->roleModel->getRoleDropdown();
    }

    /**
     * Create a new role.
     *
     * @param array $data Role creation data
     * @return array Result of creation operation
     */
    public function createRole(array $data): array
    {
        return $this->roleModel->createRole($data);
    }

    /**
     * Retrieve details of a specific role.
     *
     * @param array $data Role identifier data (e.g., ['roleId' => 1])
     * @return array Role details
     */
    public function getRole(array $data): array
    {
        return $this->roleModel->getRole($data);
    }

    /**
     * Update an existing role.
     *
     * @param array $data Role data with updates
     * @return array Result of update operation
     */
    public function updateRole(array $data): array
    {
        return $this->roleModel->updateRole($data);
    }

    /**
     * Delete a role.
     *
     * @param array $data Role identifier data for deletion
     * @return array Result of deletion operation
     */
    public function deleteRole(array $data): array
    {
        return $this->roleModel->deleteRole($data);
    }
}
