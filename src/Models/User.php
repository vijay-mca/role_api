<?php

namespace Models;

use PDO;
use Config\Database;

/**
 * User model class for handling user-related database operations.
 */
class User
{
    /**
     * @var Database Instance of the database connection wrapper.
     */
    private $db;

    /**
     * Constructor initializes the database connection.
     */
    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Fetches all users with optional search, sorting, and pagination.
     *
     * @param array $req Request parameters including:
     *                   - sortColumn (string): Column to sort by (only 'name' allowed).
     *                   - sortOrder (string): 'ASC' or 'DESC' sorting order.
     *                   - search (string): Search term to filter user names.
     *                   - pageSize (int): Number of users per page.
     *                   - pageNo (int): Page number (zero-based).
     *
     * @return array Result with status, message, and paginated user data.
     */
    public function getAllUsers($req)
    {
        try {
            // Define allowed sorting columns and orders to prevent SQL injection
            $allowedSortColumn = [
                "name"   => "u.name",
                "email"  => "u.email",
                "mobile" => "u.mobile",
                "pincode" => "u.pincode",
                "role"   => "r.role_name"
            ];

            // Validate sort column (check if key exists in allowed list)
            $requestedSortColumn = strtolower($req['sortColumn'] ?? '');
            $sortColumn = array_key_exists($requestedSortColumn, $allowedSortColumn)
                ? $allowedSortColumn[$requestedSortColumn]
                : $allowedSortColumn['name'];

            // Validate sort order
            $allowedSortOrder = ["ASC", "DESC"];
            $requestedSortOrder = strtoupper($req['sortOrder'] ?? '');
            $sortOrder = in_array($requestedSortOrder, $allowedSortOrder)
                ? $requestedSortOrder
                : "ASC";

            // Sanitize and initialize search term, page size, and page number
            $search   = trim($req['search'] ?? '');
            $pageSize = max(1, (int)($req['pageSize'] ?? 10));
            $pageNo   = max(0, (int)($req['pageNo'] ?? 0));

            $queryParams = [];

            // Base SQL query to fetch user data joined with roles
            $query = "
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.mobile,
                    u.address,
                    u.pincode,
                    GROUP_CONCAT(r.role_name ORDER BY r.role_name SEPARATOR ', ') AS roles
                FROM 
                    users u
                    LEFT JOIN roles r ON u.role_id = r.id
            ";

            // Add search filter if provided (case-insensitive)
            if ($search !== '') {
                $query .= " WHERE LOWER(u.name) LIKE ? ";
                $queryParams[] = "%" . strtolower($search) . "%";
            }

            // Group by user fields to aggregate roles
            $query .= " GROUP BY u.id, u.name, u.email, u.mobile, u.address, u.pincode ";

            // Append ORDER BY clause based on validated inputs
            $query .= " ORDER BY {$sortColumn} {$sortOrder}";

            // Fetch total users to calculate pagination info
            $totalUser = $this->db->select($query, $queryParams);
            $totalCount = count($totalUser);
            $totalPages = ceil($totalCount / $pageSize);

            // Adjust pageNo if it exceeds total pages
            if ($totalPages > 0 && $pageNo >= $totalPages) {
                $pageNo = $totalPages - 1;
            }

            // Calculate offset for LIMIT clause
            $offset = $pageNo * $pageSize;

            // Add LIMIT and OFFSET for pagination
            $query .= " LIMIT ? OFFSET ?";
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;

            // Fetch paginated users
            $users = $this->db->select($query, $queryParams);

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "",
                "data"       => [
                    "users"      => $users,
                    "totalCount" => $totalCount
                ]
            ];
        } catch (\Exception $e) {
            // Return error response on exception
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to fetch users.",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Creates a new user record in the database.
     *
     * @param array $data User details including:
     *                    - name, email, mobile, address, pincode, role, password
     *
     * @return array Result with status and created user ID if successful.
     */
    public function createUser($data)
    {
        try {
            // Validate required fields
            if (
                empty($data['name']) || empty($data['email']) || empty($data['mobile']) || empty($data['role'])
            ) {
                return [
                    "status"     => "error",
                    "statusCode" => 400,
                    "message"    => "Missing required fields."
                ];
            }

            $emailExist = $this->db->selectOne("SELECT COUNT(*) AS emailCount FROM users WHERE LOWER(email) = LOWER(?)", [$data['email']]);

            if ((int)$emailExist->emailCount > 0) {
                return [
                    "status"     => "email-exist",
                    "statusCode" => 200,
                    "message"    => "Email already exists"
                ];
            }

            $mobileExist = $this->db->selectOne("SELECT COUNT(*) AS mobileCount FROM users WHERE mobile = ?", [$data['mobile']]);

            if ((int)$mobileExist->mobileCount > 0) {
                return [
                    "status"     => "mobile-exist",
                    "statusCode" => 200,
                    "message"    => "Mobile already exists"
                ];
            }

            // Prepare INSERT query with named parameters
            $query = "INSERT INTO users (name, email, mobile, address, pincode, role_id, password) 
                      VALUES (:name, :email, :mobile, :address, :pincode, :role_id, :password)";
            $params = [
                ':name'     => $data['name'],
                ':email'    => $data['email'],
                ':mobile'   => $data['mobile'],
                ':address'  => $data['address'],
                ':pincode'  => $data['pincode'],
                ':role_id'  => $data['role'],
                ':password' => password_hash($data['password'], PASSWORD_BCRYPT) // Hash password securely
            ];

            // Execute insert and get inserted user ID
            $result = $this->db->insert($query, $params);

            return [
                "status"     => "success",
                "statusCode" => 201,
                "message"    => "User created successfully.",
                "data"       => [
                    "user_id" => $result
                ]
            ];
        } catch (\Exception $e) {
            // Return error response on failure
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to create user.",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves a single user by ID.
     *
     * @param array $data Must contain 'userId' key.
     *
     * @return array Result with user data or error if not found.
     */
    public function getUser($data)
    {
        try {
            // Validate userId parameter
            if (empty($data['userId']) || !is_numeric($data['userId'])) {
                return [
                    "status"     => "error",
                    "statusCode" => 400,
                    "message"    => "User id required"
                ];
            }

            // Fetch user from database
            $user = $this->db->selectOne("SELECT * FROM users WHERE id = ?", [$data['userId']]);
            if (!$user) {
                return [
                    "status"     => "not-found",
                    "statusCode" => 404,
                    "message"    => "User not found"
                ];
            }

            // Return success with user data
            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "User found",
                "data"       => $user
            ];
        } catch (\Exception $e) {
            // Return error response on exception
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => $e->getMessage()
            ];
        }
    }

    /**
     * Updates an existing user's details.
     *
     * @param array $data User data to update, including 'userId' and required fields.
     *                    Optional 'password' field to change password.
     *
     * @return array Result with update status.
     */
    public function updateUser($data)
    {
        try {
            // Validate userId parameter
            if (empty($data['userId']) || !is_numeric($data['userId'])) {
                return [
                    "status"     => "error",
                    "statusCode" => 400,
                    "message"    => "User id required"
                ];
            }

            // Validate required user fields
            if (
                empty($data['name']) || empty($data['email']) || empty($data['mobile']) || empty($data['role'])
            ) {
                return [
                    "status"     => "error",
                    "statusCode" => 400,
                    "message"    => "Missing required fields."
                ];
            }

            $emailExist = $this->db->selectOne("SELECT COUNT(*) AS emailCount FROM users WHERE id <> ? AND LOWER(email) = LOWER(?)", [$data['userId'], $data['email']]);

            if ((int)$emailExist->emailCount > 0) {
                return [
                    "status"     => "email-exist",
                    "statusCode" => 200,
                    "message"    => "Email already exists"
                ];
            }

            $mobileExist = $this->db->selectOne("SELECT COUNT(*) AS mobileCount FROM users WHERE id <> ? AND mobile = ?", [$data['userId'], $data['mobile']]);

            if ((int)$mobileExist->mobileCount > 0) {
                return [
                    "status"     => "mobile-exist",
                    "statusCode" => 200,
                    "message"    => "Mobile already exists"
                ];
            }

            // Prepare fields for update
            $fields = [
                'name'    => $data['name'],
                'email'   => $data['email'],
                'mobile'  => $data['mobile'],
                'address' => $data['address'],
                'pincode' => $data['pincode'],
                'role_id' => $data['role']
            ];

            $setParts = [];
            $params = [];

            // Build SQL SET parts and parameters dynamically
            foreach ($fields as $column => $value) {
                $setParts[] = "$column = :$column";
                $params[":$column"] = $value;
            }

            // If password is provided, hash and include it
            if (!empty($data['password'])) {
                $setParts[] = "password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $params[':id'] = $data['userId'];

            // Final UPDATE query with dynamic SET clause
            $query = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :id";
            $this->db->update($query, $params);

            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "User updated successfully.",
                "data"       => [
                    "user_id" => $data['userId']
                ]
            ];
        } catch (\Exception $e) {
            // Return error response on exception
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "Failed to update user.",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Deletes a user by ID.
     *
     * @param array $data Must contain 'userId'.
     *
     * @return array Result with deletion status.
     */
    public function deleteUser($data)
    {
        try {
            // Validate userId parameter
            if (empty($data['userId']) || !is_numeric($data['userId'])) {
                return [
                    "status"     => "error",
                    "statusCode" => 400,
                    "message"    => "User id required"
                ];
            }

            // Execute DELETE query
            $result = $this->db->delete("DELETE FROM users WHERE id = ?", [$data['userId']]);

            if ($result) {
                // Successfully deleted
                return [
                    "status"     => "success",
                    "statusCode" => 200,
                    "message"    => "User deleted successfully."
                ];
            } else {
                // User not found for deletion
                return [
                    "status"     => "not-found",
                    "statusCode" => 404,
                    "message"    => "User not found"
                ];
            }
        } catch (\Exception $e) {
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "An unexpected error occurred.",
                "error"      => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves detailed profile information for a user by ID.
     * Includes user details along with their role name.
     *
     * @param array $data Must contain 'userId'.
     *
     * @return array Result with profile data or error.
     */
    public function getProfile($data)
    {
        try {
            // Validate userId parameter
            if (empty($data['userId']) || !is_numeric($data['userId'])) {
                return [
                    "status"     => "error",
                    "statusCode" => 400,
                    "message"    => "User id required"
                ];
            }

            // Fetch user profile joined with role name
            $profile = $this->db->selectOne(
                "
                SELECT
                    u.id,
                    u.name,
                    u.email,
                    u.mobile,
                    u.address,
                    u.pincode,
                    r.role_name AS roleName
                FROM
                    users u
                    JOIN roles r ON u.role_id = r.id
                WHERE
                    u.id = ?
                ",
                [
                    $data['userId']
                ]
            );

            if (!$profile) {
                return [
                    "status"     => "not-found",
                    "statusCode" => 404,
                    "message"    => "User not found"
                ];
            }

            // Return profile data on success
            return [
                "status"     => "success",
                "statusCode" => 200,
                "message"    => "User found",
                "data"       => $profile
            ];
        } catch (\Exception $e) {
            return [
                "status"     => "error",
                "statusCode" => 500,
                "message"    => "An unexpected error occurred.",
                "error"      => $e->getMessage()
            ];
        }
    }
}
