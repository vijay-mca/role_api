<?php

namespace Helpers;

class RoleGuardHelper
{
    /**
     * Check if the user has any of the allowed roles
     *
     * @param int $userRole ID of the role the user has
     * @param array $allowedRoles Array of allowed role names or IDs
     * @return bool
     */
    public static function hasRole($userRole, array $allowedRoles): bool
    {
        // $userRole is a single integer, $allowedRoles is an array of integers

        // Check if the user role exists in the allowed roles array
        return in_array($userRole, $allowedRoles, true); // true for strict type checking
    }


    /**
     * Check if the user has access to a module by module ID or name
     *
     * @param array $userModules Array of module IDs or names the user has access to
     * @param mixed $moduleToCheck Module ID or name to check access for
     * @return bool
     */
    public static function hasModuleAccess($userModules, array $moduleToCheck): bool
    {
        return in_array($userModules, $moduleToCheck, true); // true for strict type checking
    }
}
