<?php

namespace Models;

use Config\Database;
use Models\Module;

/**
 * Role model class for managing role-related database operations,
 * including CRUD for roles and their associated modules.
 */
class Role
{
    /**
     * @var Database Instance of the database connection.
     */
    private $db;

    /**
     * @var Module Instance of Module model to fetch module-related data.
     */
    private $moduleModel;

    /**
     * Constructor initializes database and module model instances.
     */
    public function __construct()
    {
        $this->db = new Database();
        $this->moduleModel = new Module();
    }

    /**
     * Retrieves all roles with optional search, sorting, and pagination.
     *
     * @param array $req Request parameters:
     *                   - sortColumn (string): Column to sort by ('name' allowed).
     *                   - sortOrder (string): 'ASC' or 'DESC'.
     *                   - search (string): Search term to filter role names.
     *                   - pageSize (int): Number of roles per page.
     *                   - pageNo (int): Page number (zero-based).
     *
     * @return array Result containing roles list with total count and status.
     */
    public function getAllRoles($req)
    {
        try {
            // Validate sorting column and order to prevent SQL injection
            $allowedSortColumn = [
                "name" => "r.role_name" // You can add more mappings here if needed
            ];

            $requestedSortColumn = strtolower($req['sortColumn'] ?? '');
            $sortColumn = array_key_exists($requestedSortColumn, $allowedSortColumn)
                ? $allowedSortColumn[$requestedSortColumn]
                : $allowedSortColumn['name'];

            $allowedSortOrder = ["ASC", "DESC"];
            $requestedSortOrder = strtoupper($req['sortOrder'] ?? '');
            $sortOrder = in_array($requestedSortOrder, $allowedSortOrder)
                ? $requestedSortOrder
                : "ASC";


            // Sanitize search term and set pagination defaults
            $search   = trim($req['search'] ?? '');
            $pageSize = max(1, (int)($req['pageSize'] ?? 10));
            $pageNo   = max(0, (int)($req['pageNo'] ?? 0));

            $queryParams = [];

            // Base SQL to select roles and concatenated module names
            $query = "
                SELECT 
                    r.id,
                    r.role_name,
                    GROUP_CONCAT(m.name ORDER BY m.name SEPARATOR ', ') AS modules
                FROM roles r
                LEFT JOIN role_modules rm ON r.id = rm.role_id
                LEFT JOIN modules m ON rm.module_id = m.id
            ";

            // Add search filtering if search term is provided
            if ($search !== '') {
                $query .= " WHERE LOWER(r.name) LIKE ? ";
                $queryParams[] = "%" . strtolower($search) . "%";
            }

            // Group by role fields to aggregate module names
            $query .= " GROUP BY r.id, r.role_name ";
            // Append ORDER BY clause
            $query .= " ORDER BY {$sortColumn} {$sortOrder}";

            // Fetch all matching roles for pagination count
            $totalRoles = $this->db->select($query, $queryParams);
            $totalCount = count($totalRoles);
            $totalPages = ceil($totalCount / $pageSize);

            // Adjust pageNo if it exceeds max pages
            if ($totalPages > 0 && $pageNo >= $totalPages) {
                $pageNo = $totalPages - 1;
            }

            // Calculate offset for pagination
            $offset = $pageNo * $pageSize;

            // Add LIMIT and OFFSET for paginated results
            $query .= " LIMIT ? OFFSET ?";
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;

            // Fetch paginated roles
            $roles = $this->db->select($query, $queryParams);

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "",
                "data"       => [
                    "roles"      => $roles,
                    "totalCount" => $totalCount
                ]
            ];
        } catch (\Exception $e) {
            // Consistent catch block with descriptive error response
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to fetch roles.",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves a simple list of roles for dropdowns (id and name only).
     *
     * @return array Result with role id-name pairs or error.
     */
    public function getRoleDropdown()
    {
        try {
            // Select all roles ordered alphabetically by name
            $query = "SELECT id, role_name FROM roles ORDER BY role_name ASC";
            $roles = $this->db->select($query);

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "",
                "data"       => [
                    "roles" => $roles
                ]
            ];
        } catch (\Exception $e) {
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to fetch roles.",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Creates a new role with associated modules.
     *
     * @param array $data Role data including:
     *                    - name (string): Role name.
     *                    - modules (array): List of module IDs to assign.
     *
     * @return array Result status and message.
     */
    public function createRole($data)
    {
        try {
            // Validate required role name
            if (empty($data['name'])) {
                return [
                    "status"     => "bad_request",
                    "statusCode" => 400,
                    "message"    => "Role name is required"
                ];
            }

            $this->db->beginTransaction();

            // Insert new role record
            $roleName = trim($data['name']);

            $roleExist = $this->db->selectOne("SELECT COUNT(*) AS roleCount FROM roles WHERE LOWER(role_name) = LOWER(?)", [$roleName]);

            if ((int)$roleExist->roleCount > 0) {
                return [
                    "status"     => "role-exist",
                    "statusCode" => 200,
                    "message"    => "Role already exists"
                ];
            }

            $query = "INSERT INTO roles (role_name) VALUES (?)";
            $this->db->insert($query, [$roleName]);

            // Get the inserted role ID
            $lastAddedRoleId = $this->db->lastInsertId();
            $modules = $data['modules'] ?? [];

            // Prepare batch insert for role_modules linking role to modules
            $placeholders = [];
            $params = [];

            foreach ($modules as $moduleId) {
                $placeholders[] = "(?, ?)";
                $params[] = $lastAddedRoleId;
                $params[] = $moduleId;
            }

            if (!empty($placeholders)) {
                $query = "INSERT INTO role_modules (role_id, module_id) VALUES " . implode(", ", $placeholders);
                $this->db->insert($query, $params);
            }

            $this->db->commit();

            return [
                "status"     => "success",
                "statusCode" => 201,
                "message"    => "Role created successfully"
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();

            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to create role",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves a role by ID including assigned modules.
     *
     * @param array $data Must contain 'roleId'.
     *
     * @return array Result with role info and assigned modules or error.
     */
    public function getRole($data)
    {
        try {
            // Validate roleId parameter
            if (empty($data['roleId'])) {
                return [
                    "status"     => "bad_request",
                    "statusCode" => 400,
                    "message"    => "Role ID is required"
                ];
            }

            // Select role info and concatenated module IDs assigned
            $query = "
                SELECT 
                    r.id,
                    r.role_name,
                    GROUP_CONCAT(rm.module_id) AS modules
                FROM roles r
                LEFT JOIN role_modules rm ON r.id = rm.role_id
                WHERE r.id = ?
                GROUP BY r.id, r.role_name
            ";

            $role = $this->db->selectOne($query, [$data['roleId']]);

            if (!$role) {
                return [
                    "status"     => "not_found",
                    "statusCode" => 404,
                    "message"    => "Role not found"
                ];
            }

            // Parse module IDs string into array of integers
            $moduleIds = $role->modules ? array_map('intval', explode(',', $role->modules)) : [];

            // Fetch selected module details using Module model
            $selectedModules = $this->moduleModel->getRoleModules($data['roleId']);

            return [
                "status"     => "success",
                "statusCode" => 200,
                "data"       => [
                    "id" => (int) $role->id,
                    "name" => $role->role_name,
                    "modules" => $moduleIds,
                    "selectedModules" => $selectedModules ?? []
                ]
            ];
        } catch (\Exception $e) {
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to fetch role",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Updates an existing role and its associated modules.
     *
     * @param array $data Role data including:
     *                    - roleId (int): ID of role to update.
     *                    - name (string): Updated role name.
     *                    - modules (array): Modules to add.
     *                    - deletedModules (array): Modules to remove.
     *
     * @return array Result status and message.
     */
    public function updateRole($data)
    {
        try {
            // Validate required fields
            if (empty($data['roleId']) || empty($data['name'])) {
                return [
                    "status"     => "bad_request",
                    "statusCode" => 400,
                    "message"    => "Role ID and name are required"
                ];
            }

            $this->db->beginTransaction();

            $roleId = (int)$data['roleId'];
            $roleName = trim($data['name']);

            $roleExist = $this->db->selectOne("SELECT COUNT(*) AS roleCount FROM roles WHERE id <> ? AND LOWER(role_name) = LOWER(?)", [$roleId, $roleName]);

            if ((int)$roleExist->roleCount > 0) {
                return [
                    "status"     => "role-exist",
                    "statusCode" => 200,
                    "message"    => "Role already exists"
                ];
            }

            // Update role name
            $query = "UPDATE roles SET role_name = ? WHERE id = ?";
            $this->db->insert($query, [$roleName, $roleId]);

            // Insert new module associations if provided
            if (!empty($data['modules']) && is_array($data['modules'])) {
                $placeholders = [];
                $params = [];

                foreach ($data['modules'] as $moduleId) {
                    $placeholders[] = "(?, ?)";
                    $params[] = $roleId;
                    $params[] = $moduleId;
                }

                if (!empty($placeholders)) {
                    $query = "INSERT INTO role_modules (role_id, module_id) VALUES " . implode(", ", $placeholders);
                    $this->db->insert($query, $params);
                }
            }

            // Delete module associations if provided
            if (!empty($data['deletedModules']) && is_array($data['deletedModules'])) {
                $placeholders = implode(",", array_fill(0, count($data['deletedModules']), "?"));
                $params = $data['deletedModules'];
                array_unshift($params, $roleId);

                $query = "DELETE FROM role_modules WHERE role_id = ? AND module_id IN ($placeholders)";
                $this->db->insert($query, $params);
            }

            $this->db->commit();

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "Role updated successfully"
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();

            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to update role",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Deletes a role and its associated modules by role ID.
     *
     * @param array $data Must contain 'roleId'.
     *
     * @return array Result status and message.
     */
    public function deleteRole($data)
    {
        try {
            // Validate roleId parameter
            if (empty($data['roleId'])) {
                return [
                    "status"     => "bad_request",
                    "statusCode" => 400,
                    "message"    => "Role ID is required"
                ];
            }

            if ((int)$data['roleId'] === 1) {
                return [
                    "status"     => "bad_request",
                    "statusCode" => 400,
                    "message"    => "You can't delete the admin role"
                ];
            }

            $this->db->beginTransaction();

            $totalUser = $this->db->selectOne("SELECT COUNT(*) AS totalUserCount FROM users WHERE role_id = ?", [$data['roleId']]);
            // print_r($totalUser);
            if ((int)$totalUser->totalUserCount > 0) {
                return [
                    "status"     => "bad_request",
                    "statusCode" => 400,
                    "message"    => "You cannot delete this role because it is assigned to one or more users."
                ];
            }
            // Delete role-module associations first to maintain integrity
            $queryModules = "DELETE FROM role_modules WHERE role_id = ?";
            $this->db->delete($queryModules, [$data['roleId']]);

            // Delete the role itself
            $queryRole = "DELETE FROM roles WHERE id = ?";
            $this->db->delete($queryRole, [$data['roleId']]);

            $this->db->commit();

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "Role deleted successfully"
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();

            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to delete role",
                "error"      => $e->getMessage()
            ];
        }
    }
}
