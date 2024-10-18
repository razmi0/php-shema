<?php

/**
 * 
 * 
 *  _______________________________________________________________________
 * |                                                                       |
 * |        This class contain the core logic for building the             |
 * |        validation map. Only static methods in it for better           |
 * |        maintenability and scalability. His statics methods            |
 * |        return the rule Object.                                        |
 * |_______________________________________________________________________|
 * 
 * 
 * 
 **/

namespace Schema;

require_once 'Validator/TypeValidator.php';
require_once 'Validator/ComplexValidator.php';
require_once 'Validator/RangeValidator.php';
require_once 'Validator/ValidatorInterface.php';

use Schema\Validator\{
    StringValidator,
    DoubleValidator,
    IntegerValidator,
    ArrayValidator,
    isNullValidator,
    NotBlankValidator,
    IntegerRangeValidator,
    ArrayRangeValidator,
    StringRangeValidator,
    ValidatorInterface
};

/**
 * 
 * 
 * 
 * 
 * Class Core
 * 
 * Schema core logic for building the validation map.
 * 
 * @package Schema
 * @author Cuesta Thomas
 * @version 1.0
 * 
 * 
 * 
 * @todo Move rules to an ValidatorInterface[] to avoid switch case and better scalability when adding rules ?
 * 
 * 
 * 
 */
class Core
{
    /**
     * 
     * 
     * Type rules are processed here : string, double, integer, array, null
     * @static
     * 
     */
    static function processTypeRules($constraintValue): ValidatorInterface
    {
        switch ($constraintValue) {
            case "string":
                return new StringValidator();

            case "double":
                return new DoubleValidator();

            case "integer":
                return new IntegerValidator();

            case "array":
                return new ArrayValidator();

            case "null":
                return new isNullValidator();
        }
    }

    /**
     * 
     * 
     * Complex rules are processed here : notBlank
     * @static
     * 
     */
    static function processComplexRules($constraint): ValidatorInterface
    {
        switch ($constraint) {
            case "notBlank":
                return new NotBlankValidator();
        }
    }

    /**
     * 
     * 
     * Range rules are processed here : integer, double, array, string
     * @static
     * 
     */
    static function processRangeRules($range, $type): ValidatorInterface
    {
        switch ($type) {
            case "integer":
                return new IntegerRangeValidator($range);
            case "double":
                return new IntegerRangeValidator($range);
            case "array":
                return new ArrayRangeValidator($range);
            case "string":
                return new StringRangeValidator($range);
        }
    }
}
