<?php

namespace Schema\Validator;

use Schema\Validator\ValidatorResult as ValidatorResult;
use Schema\Validator\ValidatorInterface as ValidatorInterface;

require_once 'ValidatorInterface.php';
require_once 'ValidatorResult.php';

class StringValidator implements ValidatorInterface
{
    public function validate($value, $key): ValidatorResult
    {
        $currentType = gettype($value);
        return !is_string($value)
            ? new ValidatorResult("invalid_type", "string", $currentType, [$key], "Expected string, received " . $currentType)
            : new ValidatorResult("valid", "string", $currentType, [$key], "Expected string, received " . $currentType);
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
