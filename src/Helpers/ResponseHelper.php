<?php

namespace Helpers;

use Helpers\CryptoHelper;

class ResponseHelper
{
    /**
     * Sends a JSON response with encrypted data and appropriate HTTP status code.
     *
     * This method:
     * - Sets the Content-Type header to application/json.
     * - Sets the HTTP response status code based on the provided data or default.
     * - Encrypts the JSON-encoded response data using CryptoHelper with a generated IV.
     * - Outputs the encrypted data and IV (base64 encoded) as a JSON object.
     * - Terminates script execution immediately after sending the response.
     *
     * @param array $data    The data to be sent in the response.
     *                       Should be an associative array that will be JSON-encoded.
     *                       Optionally can include a 'statusCode' key to specify HTTP status.
     * @param int   $status  HTTP status code to send if 'statusCode' key is not present in $data.
     *                       Defaults to 200 (OK).
     * @return void
     */
    public static function jsonResponse($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($data['statusCode'] ?? $status);

        // Generate a new Initialization Vector (IV) for encryption
        $iv = CryptoHelper::generateIv();

        // Encrypt the JSON encoded data using the IV
        $encrypted = CryptoHelper::encrypt(json_encode($data), $iv);

        // Output the encrypted data and IV as JSON and terminate execution
        echo json_encode([
            'data' => $encrypted,
            'iv'   => base64_encode($iv)
        ]);
        exit();
    }

    /**
     * Helper method to send a standardized JSON error response.
     *
     * Calls jsonResponse with an error message payload and a default or provided HTTP status code.
     *
     * @param string $message Error message to send.
     * @param int    $status  HTTP status code for the error response, defaults to 400 (Bad Request).
     * @return void
     */
    public static function jsonError($message, $status = 400)
    {
        self::jsonResponse(['error' => $message], $status);
    }
}
