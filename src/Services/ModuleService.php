<?php

namespace Services;

use Models\Module;

/**
 * Service class responsible for module-related business logic.
 */
class ModuleService
{
    /**
     * @var Module Instance of the Module model
     */
    protected $moduleModel;

    /**
     * ModuleService constructor.
     * Initializes the Module model.
     */
    public function __construct()
    {
        $this->moduleModel = new Module();
    }

    /**
     * Retrieve all modules formatted for dropdown selection.
     *
     * @return array Response containing module list for dropdown
     */
    public function getModuleDropdown(): array
    {
        return $this->moduleModel->getModuleDropdown();
    }

    /**
     * Retrieve modules assigned to a specific role.
     *
     * @param int|string $roleId Role identifier
     * @return array List of modules assigned to the role
     */
    public function getRoleModules($roleId): array
    {
        return $this->moduleModel->getRoleModules($roleId);
    }
}
