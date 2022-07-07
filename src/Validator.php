<?php

declare(strict_types=1);

namespace Memcrab\Opis;

use \Opis\JsonSchema\Validator as OpisValidator;
use \Opis\JsonSchema\Errors\ErrorFormatter;
use \Opis\JsonSchema\Errors\ValidationError;

class Validator extends OpisValidator
{
    private static $instance;

    private function __construct()
    {
    }
    private function __clone()
    {
    }
    public function __wakeup()
    {
    }

    public static function setSchemasFolderPath(string $path): void
    {
        $this->validatorsFolderPath = $path;
    }

    public static function obj()
    {
        if (!(self::$instance instanceof OpisValidator)) {
            self::$instance = new self();

            self::$instance->validatorsFolderPath = "./validation";
            $validatorsFiles = scandir(self::$instance->validatorsFolderPath);

            foreach ($validatorsFiles as $file) {
                if (is_file(self::$instance->validatorsFolderPath . '/' . $file)) {
                    self::$instance->resolver()->registerFile('https://validators/' . $file, self::$instance->validatorsFolderPath . '/' . $file);
                }
            }
        }
        return self::$instance;
    }

    private function customFormat(ValidationError $error): array
    {
        $formatter = new ErrorFormatter;
        return [
            'formattedMessage' => $formatter->formatErrorMessage($error),
            'contents' => $error->schema()->info()->data(),
        ];
    }

    private function customFormatKey(): string
    {
        return 'errors';
    }

    public function validateIncomeData(\stdClass $data,  string $jsonFileName = ''): array
    {
        $result = $this->validate($data, 'https://validators/' . $jsonFileName);
        if ($result->hasError()) {
            $formatter = new ErrorFormatter;
            $error = $result->error();
            $ErroResult = $formatter->format($error, false, $this->customFormat(...), $this->customFormatKey(...));
        }
        return [
            'status' => $result->isValid(),
            'data' => isset($ErroResult['errors']['formattedMessage']) ? $ErroResult['errors']['formattedMessage'] : '',
            'code' => (isset($ErroResult['errors']['contents']->apiCode)) ? (int)$ErroResult['errors']['contents']->apiCode : 400
        ];
    }
}
