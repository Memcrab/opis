<?php

declare(strict_types=1);

namespace Memcrab\Opis;

use \Opis\JsonSchema\Validator as OpisValidator;
use \Opis\JsonSchema\Errors\ErrorFormatter;
use \Opis\JsonSchema\Errors\ValidationError;

class Validator extends OpisValidator
{
    private static $instance;
    private static $files = [];
    private static string $validatorsFolderPath = 'validators';
    private static string $schemaUrl = 'https://validators';

    private function __construct()
    {
    }
    public function __clone()
    {
    }
    public function __wakeup()
    {
    }

    public static function isValidatorExists(string $serviceActionFilePath): bool
    {
        return isset(self::$files[self::$validatorsFolderPath . '/' .  $serviceActionFilePath]);
    }

    public static function initiateValidationSchemas(string $rootPath): void
    {
        self::$instance = new OpisValidator();

        self::$validatorsFolderPath = $rootPath;
        self::scanValidatorsInFolder(self::$validatorsFolderPath);
    }

    private static function scanValidatorsInFolder(string $scanPath)
    {
        $validatorsFiles = scandir($scanPath);

        foreach ($validatorsFiles as $file) {
            if (!in_array($file, [".", ".."])) {

                $path = $scanPath . '/' . $file;

                if (is_file($path)) {
                    self::$instance->loader()->resolver()->registerFile(self::$schemaUrl . '/' . $path, $path);
                    self::$files[$path] = self::$schemaUrl . '/' . $path;
                } elseif (is_dir($path)) {
                    self::scanValidatorsInFolder($path);
                }
            }
        }
    }

    public static function obj(): self
    {
        if (!(self::$instance instanceof OpisValidator)) {
            throw new \Exception("Please initiate Validation schema first by calling `initiateValidationSchemas` method", 500);
        }
        return self::$instance;
    }

    private static function customFormat(ValidationError $error): array
    {
        $formatter = new ErrorFormatter;
        $formattedMessage = $formatter->formatErrorMessage($error);

        if (isset($error->data()->fullPath()[0])) $formattedMessage = "Validation error on `" . $error->data()->fullPath()[0] . "`. " . $formattedMessage;
        if (isset($error->data()->fullPath()[0]) && !is_object($error->data()->value())) $formattedMessage = $formattedMessage . ". But `" . $error->data()->value() . "` given." . "`. ";

        return [
            'properties' => $error->schema()->info()->data(),
            'formattedMessage' => $formattedMessage,
        ];
    }

    private static function customFormatKey(): string
    {
        return 'errors';
    }

    public static function validateIncomeData(\stdClass $data,  string $jsonFileName = ''): void
    {
        $result = self::$instance->validate($data, self::$schemaUrl . '/' . self::$validatorsFolderPath . '/' . $jsonFileName);
        if ($result->hasError()) {
            $formatter = new ErrorFormatter;
            $error = $result->error();
            $ErroResult = $formatter->format($error, false, self::customFormat(...), self::customFormatKey(...));
            if (!isset($ErroResult['errors']['properties']->errorCode)) {
                throw new \Exception("Please provide `errorCode` property to each field of validation schema", 500);
            }
            throw new \Exception($ErroResult['errors']['formattedMessage'], (int)$ErroResult['errors']['properties']->errorCode);
        }
    }
}
