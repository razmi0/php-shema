<?php

require_once "ValidatorResult.php";

use Schema\Validators\ValidatorResult;




/**
 * Class Validator
 * 
 * This class is used to validate data against a schema of rules and constraints
 * Validator can check for :
 * - required fields
 * - nullable fields
 * - types
 * - ranges
 * - lengths
 * - regex
 * - key limiter
 * 
 * Possibles types : 
 * - string
 * - integer
 * - double
 * - boolean
 * - typed array (string[], integer[], double[], boolean[], array[], object[], any[])
 * - object
 * - any
 * 
 * @todo add complex types ( string | integer, string | double, integer | double, etc)
 * 
 * @method __construct(array $schema) Initializes the validator with a schema.
 * @method Validator safeParse(array $data_dirty) Validates the data against the schema.
 * @method Validator setKeyLimiter(bool $limited) Sets the key limiter.
 * @method array getResults() Gets the validation results.
 * @method array getErrors() Gets the validation errors.
 * @method array getValids() Gets the valid fields.
 * @method bool getIsValid() Gets the overall validation status.
 * 
 */
class Validator
{

    // All properties are private to ensure encapsulation
    // --

    // The set of rules and constraints
    private ?array $schema = null;

    // The keys of the schema
    private ?array $schemaKeys = null;

    // The data to validate
    private ?array $data_dirty = null;

    // The keys that doesn't follow all the validation process
    private array $pass_keys = [];

    // The key limiter ( the maximum number of keys allowed in the data )
    private ?int $key_limiter = null;

    // The required fields map
    private array $requiredMap = [];

    // The types map ( the types of the data provided )
    private array $typesMap = [];

    // The errors and valids
    private array $errors = [];
    private array $valids = [];

    // The overall validation status
    private bool $isValid = true;



    public function __construct(array $schema)
    {
        $this->schema = $schema;
        $this->schemaKeys = array_keys($schema);
        $this->setKeyLimiter(true); // by default, the number of keys is limited to what the schema declares
    }




    /**
     * safeParse is safe to use because it doesn't throw exceptions.
     * Main method that validates the data against the schema.
     */
    public function safeParse(array $data_dirty): Validator
    {

        $this->data_dirty = $data_dirty;

        $this->limitKeysCheck();

        $this->setRequiredMap();
        $this->requiredCheck();

        $this->nullableCheck();

        $this->setTypesMap();
        $this->typesCheck();

        $this->rangeCheck();
        $this->lengthCheck();
        $this->regexCheck();

        return $this;
    }


    /**
     * This method checks if the value is nullable or not
     * If the value is nullable and is null, it's valid
     * If the value is not nullable and is null, it's invalid
     * Finnaly if the data is null, it is added to the pass_keys
     */
    private function nullableCheck()
    {
        for ($i = 0; $i < count($this->schemaKeys); $i++) {
            $key = $this->schemaKeys[$i];

            if (in_array($key, $this->pass_keys) || array_key_exists("nullable", $this->schema[$key]) == false) {
                continue;
            }

            if ($this->data_dirty[$key] === null) {
                if ($this->schema[$key]["nullable"] == true) {
                    $this->addToValids(new ValidatorResult("valid", "nullable", "null", [$key], "Value is nullable"));
                } else if ($this->schema[$key]["nullable"] == false) {
                    $this->addToErrors(new ValidatorResult("invalid_nullable", "not nullable", "null", [$key], "Value is not nullable"));
                }
                $this->pass_keys[] = $key;
            }
        }
    }



    /**
     * This method stores the required fields in the requiredMap
     */
    private function setRequiredMap()
    {
        for ($i = 0; $i < count($this->schemaKeys); $i++) {
            $key = $this->schemaKeys[$i];
            $this->requiredMap[$key] = array_key_exists($key, $this->data_dirty);
        }
    }



    /**
     * This method checks if the keys are required or not in data_dirty
     * If the key is required and is not present, it's invalid. The key is added to the pass_keys.
     * If the key is not required and is not present, it's valid
     */
    private function requiredCheck()
    {
        for ($i = 0; $i < count($this->requiredMap); $i++) {
            $key = array_keys($this->requiredMap)[$i];


            if ($this->requiredMap[$key] === false) {

                if (array_key_exists("required", $this->schema[$key]) === true) {


                    if ($this->schema[$key]["required"] === true) {

                        $this->addToErrors(new ValidatorResult("invalid_required", "required", "not defined", [$key], "Value is required"));
                        $this->pass_keys[] = $key;
                    } else if ($this->schema[$key]["required"] == false) {

                        $this->addToValids(new ValidatorResult("valid", "required", "not defined", [$key], "Value is not required"));
                    }
                }
            } else if ($this->requiredMap[$key] === true) {

                if (array_key_exists("required", $this->schema[$key]) === true) {

                    if ($this->schema[$key]["required"] == true) {

                        $this->addToValids(new ValidatorResult("valid", "required", "defined", [$key], "Value is present"));
                    }
                }
            }
        }
    }



    /**
     * This method stores the types provided in data_dirty
     * if the type is an array, it stores the types of each element in the array
     */
    private function setTypesMap()
    {
        for ($i = 0; $i < count($this->schemaKeys); $i++) {
            $key = $this->schemaKeys[$i];

            if ($this->schema[$key]["type"] === "any") {
                $this->pass_keys[] = $key;
                continue;
            }

            if (in_array($key, $this->pass_keys) || array_key_exists("type", $this->schema[$key]) == false) {
                continue;
            }


            if (str_ends_with($this->schema[$key]["type"], "[]")) {

                if (gettype($this->data_dirty[$key]) !== "array") {

                    $this->typesMap[$key] = gettype($this->data_dirty[$key]);
                    continue;
                }


                $indexedTypes = [];
                for ($j = 0; $j < count($this->data_dirty[$key]); $j++) {
                    $indexedTypes[] = gettype($this->data_dirty[$key][$j]);
                }

                $this->typesMap[$key] = $indexedTypes;
                continue;
            }

            $this->typesMap[$key] = gettype($this->data_dirty[$key]);
        }
    }


    /**
     * This method checks if the types provided in data_dirty are valid
     */
    private function typesCheck()
    {
        for ($i = 0; $i < count($this->typesMap); $i++) {
            $key = array_keys($this->typesMap)[$i];

            if (in_array($key, $this->pass_keys)) {
                continue;
            }



            if (!is_array($this->typesMap[$key])) {


                // i compare types that emerge from data_dirty and expected type from schema
                if ($this->typesMap[$key] !== $this->schema[$key]["type"]) {

                    $this->addToErrors(new ValidatorResult("invalid_type", $this->schema[$key]["type"], $this->typesMap[$key], [$key], "Invalid type"));
                } else {
                    $this->addToValids(new ValidatorResult("valid", $this->schema[$key]["type"], $this->typesMap[$key], [$key], "Valid type"));
                }
            } else if (is_array($this->typesMap[$key])) {
                if (str_ends_with($this->schema[$key]["type"], "[]")) {
                    $type = str_replace("[]", "", $this->schema[$key]["type"]);
                    $invalidTypeFound = [];
                    $hasError = false;
                    for ($j = 0; $j < count($this->typesMap[$key]); $j++) {
                        if ($this->typesMap[$key][$j] !== $type) {
                            $invalidTypeFound[] = [$this->typesMap[$key][$j], $j];
                            $hasError = true;
                        }
                    }

                    if ($hasError) {
                        for ($k = 0; $k < count($invalidTypeFound); $k++) {
                            $this->addToErrors(new ValidatorResult("invalid_type", $type, $invalidTypeFound[$k][0], [$key, $invalidTypeFound[$k][1]], "Invalid type"));
                        }
                    } else {
                        $this->addToValids(new ValidatorResult("valid", $this->schema[$key]["type"], $this->schema[$key]["type"], [$key], "Valid type"));
                    }
                }
            }
        }
    }


    /**
     * This method checks if the values provided in data_dirty are within the range (min, max) for integer and double types
     */
    private function rangeCheck()
    {
        for ($i = 0; $i < count($this->schemaKeys); $i++) {
            $key = $this->schemaKeys[$i];

            if (in_array($key, $this->pass_keys) || array_key_exists("range", $this->schema[$key]) == false) {
                continue;
            }

            $isDoubleOrInteger = $this->typesMap[$key] === "integer" || $this->typesMap[$key] === "double";


            if (array_key_exists("range", $this->schema[$key]) == true && $isDoubleOrInteger == false) {
                continue;
            }


            if ($isDoubleOrInteger) {
                $min = $this->schema[$key]["range"][0];
                $max = $this->schema[$key]["range"][1];

                if ($min !== null && $this->data_dirty[$key] < $min) {
                    $this->addToErrors(new ValidatorResult("invalid_range", "superior to " . $min, $this->data_dirty[$key], [$key], "Value is below the minimum"));
                } else if ($max !== null && $this->data_dirty[$key] > $max) {
                    $this->addToErrors(new ValidatorResult("invalid_range", "inferior to " . $max, $this->data_dirty[$key], [$key], "Value is above the maximum"));
                } else {
                    $this->addToValids(new ValidatorResult("valid", "in range " . "[" . ($min ?? "-infinity") . ", " . ($max ?? "+infinity") . "]", $this->data_dirty[$key], [$key], "Value is within the range"));
                }
            }
        }
    }


    /**
     * This method checks if the values provided in data_dirty are within the length (min, max) for string and array types
     */
    private function lengthCheck()
    {
        for ($i = 0; $i < count($this->schemaKeys); $i++) {
            $key = $this->schemaKeys[$i];

            if (in_array($key, $this->pass_keys) || array_key_exists("length", $this->schema[$key]) == false) {
                continue;
            }

            $isString = $this->typesMap[$key] === "string";
            $isArray = is_array($this->typesMap[$key]);

            if (array_key_exists("length", $this->schema[$key]) == true && $isString == false && $isArray == false) {
                continue;
            }

            if ($isString) {
                $min = $this->schema[$key]["length"][0];
                $max = $this->schema[$key]["length"][1];

                if ($min !== null && strlen($this->data_dirty[$key]) < $min) {
                    $this->addToErrors(new ValidatorResult("invalid_length", "superior to " . $min, strlen($this->data_dirty[$key]), [$key], "Value is below the minimum"));
                } else if ($max !== null && strlen($this->data_dirty[$key]) > $max) {
                    $this->addToErrors(new ValidatorResult("invalid_length", "inferior to " . $max, strlen($this->data_dirty[$key]), [$key], "Value is above the maximum"));
                } else {
                    $this->addToValids(new ValidatorResult("valid", "in length " . "[" . ($min ?? "-infinity") . ", " . ($max ?? "+infinity") . "]", strlen($this->data_dirty[$key]), [$key], "Value is within the length"));
                }
            } else if ($isArray) {
                $min = $this->schema[$key]["length"][0];
                $max = $this->schema[$key]["length"][1];

                if ($min !== null && count($this->data_dirty[$key]) < $min) {
                    $this->addToErrors(new ValidatorResult("invalid_length", "superior to " . $min, count($this->data_dirty[$key]), [$key], "Value is below the minimum"));
                } else if ($max !== null && count($this->data_dirty[$key]) > $max) {
                    $this->addToErrors(new ValidatorResult("invalid_length", "inferior to " . $max, count($this->data_dirty[$key]), [$key], "Value is above the maximum"));
                } else {
                    $this->addToValids(new ValidatorResult("valid", "in length " . "[" . ($min ?? "-infinity") . ", " . ($max ?? "+infinity") . "]", count($this->data_dirty[$key]), [$key], "Value is within the length"));
                }
            }
        }
    }


    /**
     * This method checks if the values provided in data_dirty match the regex provided in the schema
     */
    private function regexCheck()
    {
        for ($i = 0; $i < count($this->schemaKeys); $i++) {
            $key = $this->schemaKeys[$i];

            if (in_array($key, $this->pass_keys) || array_key_exists("regex", $this->schema[$key]) == false) {
                continue;
            }

            $isStringOrIsNumeric = $this->typesMap[$key] === "string" || $this->typesMap[$key] === "integer" || $this->typesMap[$key] === "double";
            $isArray = is_array($this->typesMap[$key]);


            if (array_key_exists("regex", $this->schema[$key]) == true && $isStringOrIsNumeric == false && $isArray == false) {
                continue;
            }

            if ($isStringOrIsNumeric) {
                $regex = $this->schema[$key]["regex"];

                if (preg_match($regex, $this->data_dirty[$key]) == 0) {
                    $this->addToErrors(new ValidatorResult("invalid_regex", $regex, $this->data_dirty[$key], [$key], "Value does not match the regex"));
                } else {
                    $this->addToValids(new ValidatorResult("valid", "regex " . $regex, $this->data_dirty[$key], [$key], "Value matches the regex"));
                }
            } else if ($isArray) {
                $regex = $this->schema[$key]["regex"];

                $invalidRegexFound = [];
                $hasError = false;
                for ($j = 0; $j < count($this->data_dirty[$key]); $j++) {
                    if (preg_match($regex, $this->data_dirty[$key][$j]) == 0) {
                        $invalidRegexFound[] = [$this->data_dirty[$key][$j], $j];
                        $hasError = true;
                    }
                }

                if ($hasError) {
                    for ($k = 0; $k < count($invalidRegexFound); $k++) {
                        $this->addToErrors(new ValidatorResult("invalid_regex", $regex, $invalidRegexFound[$k][0], [$key, $invalidRegexFound[$k][1]], "Value does not match the regex"));
                    }
                } else {
                    $this->addToValids(new ValidatorResult("valid", "regex " . $regex, $this->data_dirty[$key], [$key], "Value matches the regex"));
                }
            }
        }
    }


    /**
     * This method adds an error to the errors array and sets the isValid property to false
     */
    private function addToErrors(ValidatorResult $result)
    {
        $this->errors[] = $result->getReadable();
        $this->isValid = false;
    }


    /**
     * This method adds a valid results to the valids array
     */
    private function addToValids(ValidatorResult $result)
    {
        $this->valids[] = $result->getReadable();
    }


    /**
     * This method sets the key limiter ( the maximum number of keys allowed in the data )
     * if limited is true, the key limiter is set to the number of keys in the schema
     * if limited is false, the key limiter is set to null and the number of keys is not limited
     */
    public function setKeyLimiter(bool $limited): Validator
    {

        if ($limited) {
            $this->key_limiter = count($this->schemaKeys);
        } else {
            $this->key_limiter = null;
        }
        return $this;
    }


    /**
     * This method checks if the number of keys in the data is below the key limiter ( if it's set )
     */
    private function limitKeysCheck(): void
    {
        if ($this->key_limiter !== null) {
            $count = count(array_keys($this->data_dirty));
            if ($count > $this->key_limiter) {
                $excess_key = array_keys(array_diff_key($this->data_dirty, $this->schema));
                $this->addToErrors(new ValidatorResult("invalid_key_limiter", "inferior to " . $this->key_limiter, $count, $excess_key, "Number of keys is above the limit in provided data"));
            } else {
                $this->addToValids(new ValidatorResult("valid", "inferior to " . $this->key_limiter, $count, ["key_limiter"], "Number of keys is below the limit in provided data"));
            }
        }
    }

    /**
     * All validation results
     */
    public function getResults()
    {
        return array_merge($this->errors, $this->valids);
    }

    /**
     * All validation errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * All valid fields
     */
    public function getValids()
    {
        return $this->valids;
    }

    /**
     * Overall validation status
     */
    public function getIsValid()
    {
        return $this->isValid;
    }
}
