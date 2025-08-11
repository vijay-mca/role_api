<?php

namespace Models;

use Config\Database;
use Helpers\CryptoHelper;
use Models\Module;
use Models\Role;

/**
 * Authentication model handling admin and user login as well as profile retrieval.
 */
class Auth
{
    /**
     * @var Database Database connection instance
     */
    private $db;

    /**
     * @var Module Module model instance to access module-related data
     */
    protected $moduleService;

    /**
     * @var Role Role model instance to access role-related data
     */
    protected $roleService;

    /**
     * Constructor initializes database and related service instances.
     */
    public function __construct()
    {
        $this->db = new Database();
        $this->moduleService = new Module();
        $this->roleService = new Role();
    }

    /**
     * Perform admin login by validating email and fetching user info with role modules.
     *
     * @param array $data ['email' => string, 'password' => string]
     * @return array Associative array containing status, statusCode, message, and user data on success
     */
    public function adminLogin(array $data): array
    {
        try {
            $query = "
                SELECT 
                    u.id AS userId,
                    u.name AS userName,
                    u.email,
                    u.mobile,
                    u.password,
                    u.address,
                    u.pincode,
                    u.role_id,
                    r.role_name AS roleName,
                    GROUP_CONCAT(CONCAT(m.id, ':', m.name, ':', m.route_slug) ORDER BY m.id SEPARATOR ',') AS modules
                FROM users u
                JOIN roles r ON u.role_id = r.id
                JOIN role_modules rm ON r.id = rm.role_id
                JOIN modules m ON rm.module_id = m.id
                WHERE u.role_id = ?
                AND LOWER(u.email) = LOWER(?)
                GROUP BY 
                    u.id, u.name, u.email, u.mobile, u.password, u.address, 
                    u.pincode, u.role_id, r.role_name
            ";
            $user = $this->db->selectOne($query, [1, $data['email']]);
            if (!$user) {
                return [
                    "status"     => "invalid_user",
                    "statusCode" => 401,
                    "message"    => "Invalid email or password."
                ];
            }

            $roles = $this->roleService->getRoleDropdown();
            $user->roles = $roles['data']['roles'] ?? [];

            $roleModules = $this->moduleService->getRoleModules($user->role_id ?? 0);
            $user->roleModules = $roleModules;

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "",
                "data"       => [
                    "user" => $user
                ]
            ];
        } catch (\Exception $e) {
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to fetch user",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Perform regular user login by validating email and fetching user info with role modules.
     *
     * @param array $data ['email' => string, 'password' => string]
     * @return array Associative array containing status, statusCode, message, and user data on success
     */
    public function userLogin(array $data): array
    {
        try {
            $query = "
                SELECT 
                    u.id AS userId,
                    u.name AS userName,
                    u.email,
                    u.mobile,
                    u.password,
                    u.address,
                    u.pincode,
                    u.role_id,
                    r.role_name AS roleName,
                    GROUP_CONCAT(CONCAT(m.id, ':', m.name, ':', m.route_slug) ORDER BY m.id SEPARATOR ',') AS modules
                FROM users u
                JOIN roles r ON u.role_id = r.id
                JOIN role_modules rm ON r.id = rm.role_id
                JOIN modules m ON rm.module_id = m.id
                WHERE u.role_id <> ?
                AND LOWER(u.email) = LOWER(?)
                GROUP BY 
                    u.id, u.name, u.email, u.mobile, u.password, u.address, 
                    u.pincode, u.role_id, r.role_name
            ";
            $user = $this->db->selectOne($query, [1, $data['email']]);
            if (!$user) {
                return [
                    "status"     => "invalid_user",
                    "statusCode" => 401,
                    "message"    => "Invalid email or password."
                ];
            }

            $roles = $this->roleService->getRoleDropdown();
            $user->roles = $roles['data']['roles'] ?? [];

            $roleModules = $this->moduleService->getRoleModules($user->role_id ?? 0);
            $user->roleModules = $roleModules;

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "",
                "data"       => [
                    "user" => $user,
                    "data" => $data
                ]
            ];
        } catch (\Exception $e) {
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to fetch user",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve user profile details by user ID.
     *
     * @param int|string $userId User identifier
     * @return array Associative array containing status, statusCode, message, and profile data on success
     */
    public function profile($userId): array
    {
        try {
            $query = "
                SELECT 
                    u.id AS userId,
                    u.name AS userName,
                    u.email,
                    u.mobile,
                    u.password,
                    u.address,
                    u.pincode,
                    u.role_id,
                    r.role_name AS roleName
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?
            ";
            $profile = $this->db->selectOne($query, [$userId]);
            if (!$profile) {
                return [
                    "status"     => "invalid_user",
                    "statusCode" => 401,
                    "message"    => "Invalid user ID."
                ];
            }

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "",
                "data"       => $profile
            ];
        } catch (\Exception $e) {
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to fetch user profile",
                "error"      => $e->getMessage()
            ];
        }
    }
}
