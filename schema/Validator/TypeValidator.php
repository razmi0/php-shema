<?php

require_once 'ValidatorInterface.php';
require_once 'ValidatorResult.php';

class StringValidator implements ValidatorInterface
{
    public function validate($value, $key)
    {
        $currentType = gettype($value);
        return !is_string($value)
            ? new ValidatorResult("invalid_type", "string", $currentType, [$key], "Expected string, received " . $currentType)
            : new ValidatorResult("valid", "string", $currentType, [$key], "Expected string, received " . $currentType);
    }
}

/**
 * 
 * Class NotBlankValidator
 * @description Validates that a value is not blank - meaning not equal to a blank string, a blank array, false or null.
 * @todo Add null behavior configuration to allow or disallow null values.
 */
class NotBlankValidator implements ValidatorInterface
{
    public function validate($value, $key)
    {
        return !isset($value) || $value === "" || $value === false || (is_array($value) && count($value) === 0)
            ? new ValidatorResult("not_blank", "not_blank", "blank", [$key], "Value cannot be blank")
            : new ValidatorResult("valid", "not_blank", "not_blank", [$key], "Value is not blank");
    }
}

class DoubleValidator implements ValidatorInterface
{
    public function validate($value, $key)
    {
        $currentType = gettype($value);
        return !is_double($value)
            ? new ValidatorResult("invalid_type", "double", $currentType, [$key], "Expected double, received " . $currentType)
            : new ValidatorResult("valid", "double", $currentType, [$key], "Expected double, received " . $currentType);
    }
}

class IntegerValidator implements ValidatorInterface
{
    public function validate($value, $key)
    {
        $currentType = gettype($value);
        return !is_int($value)
            ? new ValidatorResult("invalid_type", "integer", $currentType, [$key], "Expected integer, received " . $currentType)
            : new ValidatorResult("valid", "integer", $currentType, [$key], "Expected integer, received " . $currentType);
    }
}

class ArrayValidator implements ValidatorInterface
{
    public function validate($value, $key)
    {
        $currentType = gettype($value);
        return !is_array($value)
            ? new ValidatorResult("invalid_type", "array", $currentType, [$key], "Expected array, received " . $currentType)
            : new ValidatorResult("valid", "array", $currentType, [$key], "Expected array, received " . $currentType);
    }
}

class isNullValidator implements ValidatorInterface
{

    public function validate($value, $key)
    {
        $currentType = gettype($value);
        return !is_null($value)
            ? new ValidatorResult("invalid_type", "null", $currentType, [$key], "Expected null, received " . $currentType)
            : new ValidatorResult("valid", "null", $currentType, [$key], "Expected null, received " . $currentType);
    }
}
