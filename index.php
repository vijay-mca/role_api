<?php
require __DIR__ . '/vendor/autoload.php'; // <-- Add this line

use Controllers\UserController;
use Config\EnvLoader;
use Controllers\AuthController;
use Controllers\ModuleController;
use Controllers\RoleController;
use Helpers\CryptoHelper;
use Helpers\ResponseHelper;
use Middlewares\AuthMiddleware;
use Services\ModuleService;

header('Content-Type: application/json');

// Allow CORS for all requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-IV, Authorization, X-API-USER, X-API-PASS, Module, Role');

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


$envLoader = new EnvLoader();
$envLoader->load();

// Get ApiUser and ApiPass from request headers
$headers = getallheaders();

$apiUser = $headers['X-API-USER'] ?? null;
$apiPass = $headers['X-API-PASS'] ?? null;
$apiIv = base64_decode($headers['X-IV']) ?? null;
// Simple validation: check if headers exist and match expected values from env or config
$expectedApiUser = $_ENV['API_USER'];
$expectedApiPass = $_ENV['API_PASS'];
// Validate presence
if (empty($apiUser) || empty($apiPass)) {
    ResponseHelper::jsonResponse([
        'status' => 'error',
        'statusCode' => 401,
        'message' => 'Missing API credentials in headers.'
    ]);
}

$decryptedUser = CryptoHelper::decrypt($apiUser, $apiIv);
$decryptedPass = CryptoHelper::decrypt($apiPass, $apiIv);

// Validate correctness (constant time compare recommended for passwords)
if (!hash_equals($expectedApiUser, $decryptedUser) || !hash_equals($expectedApiPass, $decryptedPass)) {
    ResponseHelper::jsonResponse([
        'status' => 'error',
        'statusCode' => 401,
        'message' => 'Missing API credentials in headers.'
    ]);
}


$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Remove base path if present
$basePath = '/role_api';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
// Read request
$input = json_decode(file_get_contents('php://input'), true);
$body = null;
if (isset($input['data']) && isset($input['iv'])) {
    // Decode from Base64
    $ciphertext = $input['data'];
    $iv = base64_decode($input['iv']);

    // // Decrypt JSON string from Angular
    $requestData = CryptoHelper::decrypt($ciphertext, $iv);
    $body = json_decode($requestData, true);
}

$moduleService = new ModuleService();
if ($requestUri === '/verify' && $requestMethod === 'GET') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new UserController();
    $controller->verifyUser($userData);
} elseif ($requestUri === '/admin/login' && $requestMethod === 'POST') {
    $controller = new AuthController();
    $controller->login($body);
} elseif ($requestUri === '/admin/login' && $requestMethod === 'POST') {
    $controller = new AuthController();
    $controller->login($body);
} elseif ($requestUri === '/profile' && $requestMethod === 'GET') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    // print_r($userData->data);
    $userId = $userData->data->id;
    $controller = new AuthController();
    $controller->profile($userId);
} elseif ($requestUri === '/users' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $roleId = $userData->data->role;
    $controller = new UserController();
    $controller->getUsers($body, $roleId);
} elseif ($requestUri === '/users/add' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new UserController();
    $controller->createUser($body);
} elseif ($requestUri === '/users/get' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new UserController();
    $controller->getUser($body);
} elseif ($requestUri === '/users/update' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new UserController();
    $controller->updateUser($body);
} elseif ($requestUri === '/users/delete' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new UserController();
    $controller->deleteUser($body);
} elseif ($requestUri === '/users/modules' && $requestMethod === 'GET') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new ModuleController();
    $controller->getUserModules();
} elseif ($requestUri === '/roles' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $roleId = $userData->data->role;
    $controller = new RoleController();
    $controller->getAllRoles($body, $roleId);
} elseif ($requestUri === '/roles/add' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new RoleController();
    $controller->createRole($body);
} elseif ($requestUri === '/roles/get' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new RoleController();
    $controller->getRole($body);
} elseif ($requestUri === '/roles/update' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new RoleController();
    $controller->updateRole($body);
} elseif ($requestUri === '/roles/delete' && $requestMethod === 'POST') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new RoleController();
    $controller->deleteRole($body);
} elseif ($requestUri === '/roles/dropdown' && $requestMethod === 'GET') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new RoleController();
    $controller->getRoleDropdown();
} elseif ($requestUri === '/modules/dropdown' && $requestMethod === 'GET') {
    $userData = AuthMiddleware::requireAuth(); // Verify token before controller
    $controller = new ModuleController();
    $controller->getModuleDropdown();
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Not Found']);
}
