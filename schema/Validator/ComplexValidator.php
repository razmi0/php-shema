<?php

namespace Schema\Validator;

use Schema\Validator\ValidatorInterface;
use Schema\Validator\ValidatorResult;





/**
 * 
 * Class NotBlankValidator
 * 
 * 
 * Validates that a value is not blank - meaning not equal to a blank string, a blank array, false or null.
 * 
 * 
 * @todo Add null behavior configuration to allow or disallow null values.
 * 
 * 
 */
class NotBlankValidator implements ValidatorInterface
{
    /**
     * 
     * 
     * 
     * 
     * A value is not blank if it is not equal to a blank string, a blank array, false or null like in Symphony NotBlank constraint for forms.
     * 
     * 
     * 
     */
    public function validate(mixed $value, string $key): ValidatorResult
    {
        return !isset($value) || $value === "" || $value === false || (is_array($value) && count($value) === 0)
            ? new ValidatorResult("not_blank", "not_blank", "blank", [$key], "Value cannot be blank")
            : new ValidatorResult("valid", "not_blank", "not_blank", [$key], "Value is not blank");
    }
}



class RegexValidator implements ValidatorInterface
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function validate(mixed $value, string $key): ValidatorResult
    {
        return !preg_match($this->pattern, $value)
            ? new ValidatorResult("invalid_pattern", $this->pattern, $value, [$key], "Value does not match pattern")
            : new ValidatorResult("valid", $this->pattern, $value, [$key], "Value matches pattern");
    }
}
