<?php

namespace Schema\Template;

use InvalidArgumentException;

class Template
{
    private const REQUIRED_KEYS = ['type'];
    private const ALLOWED_TYPES = ['null', 'string', 'double', 'integer', 'array'];
    private const OPTIONAL_KEYS = ['range'];
    private array $template;

    private function __construct(array $consumerTemplate)
    {
        $this->template = $consumerTemplate;
    }

    private static function validate(array $unknownTemplate): bool
    {
        foreach ($unknownTemplate as $key => $value) {
            if (!is_array($value)) {
                throw new InvalidArgumentException("Template values must be arrays.");
            }

            // Validate required keys
            // --
            foreach (self::REQUIRED_KEYS as $requiredKey) {
                if (!array_key_exists($requiredKey, $value)) {
                    throw new InvalidArgumentException("Missing required key: '$requiredKey' for '$key'.");
                }
            }

            // Validate optional keys
            // --
            foreach (self::OPTIONAL_KEYS as $optionalKey) {
                if (array_key_exists($optionalKey, $value) && !is_array($value[$optionalKey])) {
                    throw new InvalidArgumentException("Optional key '$optionalKey' for '$key' must have an array value.");
                }
            }

            // Validate type values
            // --
            $type = $value['type'];
            if (!in_array($type, self::ALLOWED_TYPES)) {
                throw new InvalidArgumentException("Invalid type: '$type' for '$key'.");
            }

            // Validate range values
            // --
            if (array_key_exists('range', $value)) {
                if (!is_array($value['range']) || count($value['range']) !== 2) {
                    throw new InvalidArgumentException("Invalid range format for '$key'. Must be a tuple[min,max].");
                }

                foreach ($value['range'] as $rangeValue) {
                    if (!is_numeric($rangeValue) && $rangeValue !== null) {
                        throw new InvalidArgumentException("Range values must be numeric for '$key'.");
                    }
                }
            }
        }
        return true;
    }

    public function getTemplate(): array
    {
        return $this->template;
    }

    public static function fromArray(array $consumerTemplate): Template
    {

        $isValid = self::validate($consumerTemplate);

        // The array provided by the consumer is validated and can be trusted.
        // --

        if ($isValid) {
            return new Template($consumerTemplate);
        }
    }
}
