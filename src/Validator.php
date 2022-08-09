<?php

declare(strict_types=1);

namespace Memcrab\Opis;

use \Opis\JsonSchema\Validator as OpisValidator;
use \Opis\JsonSchema\Errors\ErrorFormatter;
use \Opis\JsonSchema\Errors\ValidationError;

class Validator extends OpisValidator
{
    private static $instance;
    private static string $validatorsFolderPath = './validators';
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

    public static function initiateValidationSchemas(string $path): void
    {
        self::$instance = new OpisValidator();

        self::$validatorsFolderPath = $path;
        $validatorsFiles = scandir(self::$validatorsFolderPath);

        foreach ($validatorsFiles as $file) {
            if (is_file(self::$validatorsFolderPath . '/' . $file)) {
                self::$instance->loader()->resolver()->registerFile('https://validators/' . $file, self::$validatorsFolderPath . '/' . $file);
            }
        }
    }

    public static function obj()
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
        if (isset($error->data()->fullPath()[0])) $formattedMessage = $formattedMessage . ". But `" . $error->data()->value() . "` given." . "`. ";

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
        $result = self::$instance->validate($data, self::$schemaUrl . '/' . $jsonFileName);
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
