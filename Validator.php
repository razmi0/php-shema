<?php

require_once "ValidatorResult.php";

use Schema\Validators\ValidatorResult;

class Validator
{



    private $schema = null;
    private $schemaKeys = null;
    private $data_dirty = null;
    private $pass_keys = [];

    private $requiredMap = [];
    private $typesMap = [];

    private $errors = [];
    private $valids = [];

    private $isValid = true;



    public function __construct(array $schema)
    {
        $this->schema = $schema;
        $this->schemaKeys = array_keys($schema);
    }



    public function safeParse(array $data_dirty): Validator
    {

        $this->data_dirty = $data_dirty;

        $this->setRequiredMap();
        $this->requiredCheck();
        $this->setTypesMap();
        $this->typesCheck();
        $this->rangeCheck();
        $this->lengthCheck();
        $this->regexCheck();
        // $this->notBlankCheck();








        return $this;
    }





    private function setRequiredMap()
    {
        for ($i = 0; $i < count($this->schemaKeys); $i++) {
            $key = $this->schemaKeys[$i];
            $this->requiredMap[$key] = array_key_exists($key, $this->data_dirty);
        }
    }

    private function setTypesMap()
    {
        for ($i = 0; $i < count($this->schemaKeys); $i++) {
            $key = $this->schemaKeys[$i];

            if (in_array($key, $this->pass_keys) || array_key_exists("type", $this->schema[$key]) == false || $this->schema[$key]["type"] === "any") {
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

    private function requiredCheck()
    {
        for ($i = 0; $i < count($this->requiredMap); $i++) {
            $key = array_keys($this->requiredMap)[$i];

            if ($this->requiredMap[$key] === false) {

                if (in_array("required", $this->schema[$key])) {

                    if ($this->schema[$key]["required"] == true) {

                        $this->addToErrors(new ValidatorResult("invalid_required", "required", "not defined", [$key], "Value is required"));
                        $this->pass_keys[] = $key;
                    } else if ($this->schema[$key]["required"] == false) {

                        $this->addToValids(new ValidatorResult("valid", "required", "not defined", [$key], "Value is not required"));
                    }
                }
            } else if ($this->requiredMap[$key] === true) {

                if (in_array("required", $this->schema[$key])) {

                    if ($this->schema[$key]["required"] == true) {

                        $this->addToValids(new ValidatorResult("valid", "required", "defined", [$key], "Value is present"));
                    }
                }
            }
        }
    }

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

    private function addToErrors(ValidatorResult $result)
    {
        $this->errors[] = $result->getReadable();
        $this->isValid = false;
    }

    private function addToValids(ValidatorResult $result)
    {
        $this->valids[] = $result->getReadable();
    }

    public function getResults()
    {
        return array_merge($this->errors, $this->valids);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getValids()
    {
        return $this->valids;
    }

    public function getIsValid()
    {
        return $this->isValid;
    }
}


$t1 = microtime(true);

//1
$validator = new Validator([
    "id" => [
        "type" => "integer",
        "regex" => "/^[0-9]+$/",
        "required" => true
    ],
    "name" => [
        "type" => "integer",
        "length" => [0, 10],
        "regex" => "/^[a-zA-Z]+$/",
        "required" => false
    ],
    "description" => [
        "type" => "string",
    ],
    "price" => [
        "type" => "double",
        "range" => [0, 1000]
    ],
    "variants" => [
        "type" => "string[]",
        "length" => [1, 2],
        "regex" => "/^[a-zA-Z]+$/"
    ]
]);


// // 2
// $validator_two = new Validator(
//     [
//         "ids" => [
//             "type" => "string[]",
//             "required" => true,
//             "length" => [1, 2]
//         ]
//     ]
// );



// // 3
$validator_three = new Validator(
    [
        "somekey" => [
            "type" => null,
            "required" => false
        ]
    ]
);

// 3
$data_three = [
    "somekey" => null
];

// 2
$data_two = [
    "ids" => ["1", "2", "3"]
];

// if (is_null($data_three["somekey"])) {
//     echo "null";
// }

// 1
$data = [
    "id" => "1",
    "name" => "Product",
    "description" => "This is a product",
    "price" => 12.0,
    "quantity" => "10",
    "variants" => ["variant 1", "variant 2"]
];

// // 1
$validator->safeParse($data);
$errors = $validator->getErrors();

// // 2
// $validator_two->safeParse($data_two);
// $errors_two = $validator_two->getErrors();

// // 3
$validator_three->safeParse($data_three);
$errors_three = $validator_three->getErrors();

// var_export(json_encode($errors, JSON_PRETTY_PRINT));
// var_export(json_encode($errors_two, JSON_PRETTY_PRINT));
var_export(json_encode($errors_three, JSON_PRETTY_PRINT));


$t2 = microtime(true);
echo "\n\n" . (($t2 - $t1) * 1000) . " ms\n";
