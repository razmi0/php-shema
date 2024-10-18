<?php


/**
 * 
 * 
 *  _______________________________________________________________________
 * |                                                                       |
 * |        This class aim to validate the schema provided by              |
 * |        the Schema consumer so the schema is correctly                 |
 * |        implemented.                                                   |
 * |                                                                       |
 * |        Here is a schema template :                                    |
 * |        [                                                              |
 * |           "id" => [type => integer, range => [0, null],               |
 * |           "name" => [type => string, range => [10, 65],               |
 * |           ...more keys                                                |
 * |        ]                                                              |
 * |_______________________________________________________________________|
 * 
 * 
 * 
 **/


namespace Schema;

use InvalidArgumentException;



/**
 * 
 * 
 * class Template
 * 
 * A template is a schema that can't be trusted yet. It needs to be validated first.
 * 
 * @package Schema\Template
 * @version 1.0
 * @author Cuesta Thomas
 * 
 * 
 * @todo Export all constats to a separate dedicated class for better maintenance and scalability ?
 * 
 */
class Template
{
    /**
     * 
     * 
     * All the required keys in a shema template.
     * 
     * 
     */
    private const REQUIRED_KEYS = ['type'];
    /**
     * 
     * 
     * All the allowed values for the type key in a schema template.
     * 
     * 
     */
    private const ALLOWED_TYPES = ['null', 'string', 'double', 'integer', 'array'];
    /**
     * 
     * 
     * All the optional keys in a schema template.
     * 
     * 
     */
    private const OPTIONAL_KEYS = ['range'];

    /**
     * 
     * 
     * A valid schema template.
     * 
     * 
     */
    private array $trustedTemplate;


    /**
     * 
     * 
     * The consumer template needs to be validated before being trusted.
     * @param array $consumerTemplate
     * 
     * 
     */
    private function __construct(array $consumerTemplate)
    {
        $this->trustedTemplate = $consumerTemplate;
    }


    /**
     * 
     * 
     * Validate the schema template provided by the consumer.
     * @static 
     * @param array $consumerTemplate
     * 
     * 
     */
    private static function validate(array $consumerTemplate): bool
    {
        foreach ($consumerTemplate as $key => $value) {
            // All values in the template must be arrays
            // --
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
        return $this->trustedTemplate;
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
