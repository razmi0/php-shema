<?php


// zod is a javascript library that allows you to validate objects against a set of rules.
// here is a soft implementation in php that will be used to validate the structure of the json object that will be sent to the server.


/**
 * 
 * Class Shema
 */
class Schema
{
    private $validationMap = [];

    public function addSchema(array $schema)
    {

        foreach ($schema as $key => $value) {
            foreach ($value as $constraint => $constraintValue) {
                $rule = null;
                $type = $value["type"];
                var_dump("Type: " . $type);
                var_dump("Constraint: " . $constraint);
                if ($constraint == "type") {
                    switch ($constraintValue) {
                        case "string":
                            $rule = new StringValidator();
                            break;
                        case "float":
                            $rule = new FloatValidator();
                            break;
                        case "integer":
                            $rule = new IntegerValidator();
                            break;
                        case "array":
                            $rule = new ArrayValidator();
                            break;
                        case "null":
                            $rule = new isNullValidator();
                            break;
                    }
                } else if ($constraintValue == true) {
                    switch ($constraint) {
                        case "notBlank":
                            $rule = new NotBlankValidator();
                            break;
                    }
                    var_dump($constraint);
                } else if ($constraint == "min" || $constraint == "max") {
                    // var_dump($constraint);
                    switch ($type) {
                        case "integer":
                            $rule = new IntegerRangeValidator($value["min"] ?? null, $value["max"] ?? null);
                            break;
                        case "float":
                            $rule = new IntegerRangeValidator($value["min"] ?? null, $value["max"] ?? null);
                            break;
                        case "array":
                            $rule = new ArrayRangeValidator($value["min"] ?? null, $value["max"] ?? null);
                            break;
                        case "string":
                            $rule = new StringRangeValidator($value["min"] ?? null, $value["max"] ?? null);
                            break;
                    }
                }
                $this->validationMap[$key][] = $rule;
            }
        }

        return $this;
    }

    public function parse($json)
    {
        $results = [];
        $data = json_decode($json, true);
        // echo "<pre>" . var_export($this->validationMap) . "</pre>";
        foreach ($this->validationMap as $key => $rules) {
            foreach ($rules as $rule) {
                array_push($results, $rule->validate($data[$key], $key)());
            }
        }
        return $results;
    }
}


interface ValidatorInterface
{
    public function validate($value, $key);
}

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
        return !isset($value) || $value == "" || $value == false || (is_array($value) && count($value) == 0)
            ? new ValidatorResult("not_blank", "not_blank", "blank", [$key], "Value cannot be blank")
            : new ValidatorResult("valid", "not_blank", "not_blank", [$key], "Value is not blank");
    }
}

class FloatValidator implements ValidatorInterface
{
    public function validate($value, $key)
    {
        $currentType = gettype($value);
        return !is_float($value)
            ? new ValidatorResult("invalid_type", "float", $currentType, [$key], "Expected float, received " . $currentType)
            : new ValidatorResult("valid", "float", $currentType, [$key], "Expected float, received " . $currentType);
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

class RangeValidator
{
    protected $min;
    protected $max;

    public function __construct($min = null, $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }
}

class IntegerRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct($min = null, $max = null)
    {
        parent::__construct($min, $max);
    }


    public function validate($value, $key)
    {
        if (isset($this->min) && isset($this->max)) {
            return $value < $this->min || $value > $this->max
                ? new ValidatorResult("out_of_range", "between " . $this->min . " and " . $this->max, $value, [$key], "Value is out of range")
                : new ValidatorResult("valid", "between " . $this->min . " and " . $this->max, $value, [$key], "Value is within range");
        } else if (isset($this->min) && !isset($this->max)) {
            return $value < $this->min
                ? new ValidatorResult("out_of_range", "greater than " . $this->min, $value, [$key], "Value is out of range")
                : new ValidatorResult("valid", "greater than " . $this->min, $value, [$key], "Value is within range");
        } else if (isset($this->max) && !isset($this->min)) {
            return $value > $this->max
                ? new ValidatorResult("out_of_range", "less than " . $this->max, $value, [$key], "Value is out of range")
                : new ValidatorResult("valid", "less than " . $this->max, $value, [$key], "Value is within range");
        }
    }
}

class ArrayRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct($min = null, $max = null)
    {
        parent::__construct($min, $max);
    }

    public function validate($value, $key)
    {
        $count = count($value);

        if (isset($this->min) && isset($this->max)) {
            return $count < $this->min || $count > $this->max
                ? new ValidatorResult("out_of_range", "between " . $this->min . " and " . $this->max, $count, [$key], "Array is out of range")
                : new ValidatorResult("valid", "between " . $this->min . " and " . $this->max, $count, [$key], "Array is within range");
        } else if (isset($this->min) && !isset($this->max)) {
            return $count < $this->min
                ? new ValidatorResult("out_of_range", "greater than " . $this->min, $count, [$key], "Array is out of range")
                : new ValidatorResult("valid", "greater than " . $this->min, $count, [$key], "Array is within range");
        } else if (isset($this->max) && !isset($this->min)) {
            return $count > $this->max
                ? new ValidatorResult("out_of_range", "less than " . $this->max, $count, [$key], "Array is out of range")
                : new ValidatorResult("valid", "less than " . $this->max, $count, [$key], "Array is within range");
        }
    }
}

class StringRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct($min = null, $max = null)
    {
        parent::__construct($min, $max);
    }

    public function validate($value, $key)
    {
        $size = strlen($value);

        if (isset($this->min) && isset($this->max)) {
            return $size < $this->min || $size > $this->max
                ? new ValidatorResult("out_of_range", "between " . $this->min . " and " . $this->max, $size, [$key], "String is out of range")
                : new ValidatorResult("valid", "between " . $this->min . " and " . $this->max, $size, [$key], "String is within range");
        } else if (isset($this->min) && !isset($this->max)) {
            return $size < $this->min
                ? new ValidatorResult("out_of_range", "greater than " . $this->min, $size, [$key], "String is out of range")
                : new ValidatorResult("valid", "greater than " . $this->min, $size, [$key], "String is within range");
        } else if (isset($this->max) && !isset($this->min)) {
            return $size > $this->max
                ? new ValidatorResult("out_of_range", "less than " . $this->max, $size, [$key], "String is out of range")
                : new ValidatorResult("valid", "less than " . $this->max, $size, [$key], "String is within range");
        }
    }
}



/**
 * 
 * Class ValidatorResult
 * @description A class that represents the result of a validation.
 * @param string $code The code of the validation result.
 * @param string $expected The expected value.
 * @param string $received The received value.
 * @param array $path The path to the value that was validated.
 * @param string $message A message that describes the result of the validation.
 * @return array
 */
class ValidatorResult
{
    private $code;
    private $expected;
    private $received;
    private $path;
    private $message;

    public function __construct($code, $expected, $received, $path, $message)
    {
        $this->code = $code;
        $this->expected = $expected;
        $this->received = $received;
        $this->path = $path;
        $this->message = $message;
    }

    public function __invoke()
    {
        return [
            "code" => $this->code,
            "expected" => $this->expected,
            "received" => $this->received,
            "path" => $this->path,
            "message" => $this->message
        ];
    }
}
