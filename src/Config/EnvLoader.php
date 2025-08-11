<?php
namespace Config;

use Dotenv\Dotenv;

class EnvLoader
{
    /**
     * Loads environment variables from the .env file into the PHP environment.
     * 
     * This method uses the vlucas/phpdotenv library to parse and load
     * variables from the .env file located two directories above this class file.
     * 
     * @return void
     * 
     * @throws \Dotenv\Exception\InvalidPathException If the .env file is missing or unreadable
     */
    public function load(): void
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();
    }
}
