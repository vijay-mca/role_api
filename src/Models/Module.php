<?php

namespace Models;

use Config\Database;
use Helpers\CryptoHelper;

/**
 * Module model class to manage modules and their retrieval for dropdowns
 * and role-module associations.
 */
class Module
{
    /**
     * @var Database Instance of the database connection.
     */
    private $db;

    /**
     * Constructor initializes the database connection instance.
     */
    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Retrieves all modules for use in dropdowns or selection lists.
     *
     * @return array Result containing list of modules or error message.
     */
    public function getModuleDropdown()
    {
        try {
            // Query to select module IDs and names, ordered alphabetically by name
            $query = "SELECT id, name FROM modules ORDER BY name ASC";
            $modules = $this->db->select($query);

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "",
                "data"       => [
                    "modules" => $modules
                ]
            ];
        } catch (\Exception $e) {
            // Consistent error response on failure
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to fetch modules.",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves module IDs associated with a given role.
     *
     * @param int $roleId The ID of the role whose modules to fetch.
     *
     * @return array List of module IDs assigned to the role; empty array on error.
     */
    public function getRoleModules($roleId)
    {
        try {
            // SQL joins modules with role_modules table filtered by roleId
            $query = "
                SELECT
                    m.id
                FROM
                    modules m
                    INNER JOIN role_modules rm ON m.id = rm.module_id
                WHERE
                    rm.role_id = ?
                ORDER BY
                    m.name ASC
            ";

            // Fetch modules linked to the role
            $roleModules = $this->db->select($query, [$roleId]);

            // Extract only the 'id' values and cast each to integer
            return $roleModules ? array_map('intval', array_column($roleModules, 'id')) : [];
        } catch (\Throwable $th) {
            // Return empty array on any failure or exception
            return [];
        }
    }
}
