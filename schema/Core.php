<?php

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

class Core
{
    /**
     * 
     * 
     * Type rules are processed here : string, double, integer, array, null
     * 
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
     * 
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
     * 
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
