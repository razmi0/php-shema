<?php

namespace Schema\Validator;

use Schema\Validator\ValidatorInterface;
use Schema\Validator\ValidatorResult;



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
