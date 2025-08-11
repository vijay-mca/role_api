<?php
namespace Controllers;

use Helpers\JwtHelper;
use Services\ModuleService;
use Helpers\ResponseHelper;

/**
 * Controller responsible for module-related API endpoints
 */
class ModuleController
{
    /**
     * @var ModuleService Service handling module business logic
     */
    private $moduleService;

    /**
     * ModuleController constructor.
     * Initializes ModuleService instance.
     */
    public function __construct()
    {
        $this->moduleService = new ModuleService();
    }

    /**
     * Retrieves a list of modules formatted for dropdown selection.
     *
     * @return void Outputs JSON response with modules data.
     */
    public function getModuleDropdown(): void
    {
        $result = $this->moduleService->getModuleDropdown();
        ResponseHelper::jsonResponse($result);
    }

    /**
     * Retrieves modules assigned to the currently authenticated user by decoding JWT token.
     *
     * @return void Outputs JSON response with user modules or error.
     */
    public function getUserModules(): void
    {
        $result = JwtHelper::decodeToken();
        // For debugging: echo json_encode($result);
        ResponseHelper::jsonResponse($result, $result['statusCode']);
    }
}
