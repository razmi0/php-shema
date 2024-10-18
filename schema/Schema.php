<?php

require_once 'Page.php';
require_once 'Validator/TypeValidator.php';
require_once 'Validator/RangeValidator.php';
require_once 'Validator/ValidatorError.php';
// zod is a javascript library that allows you to validate objects against a set of rules.
// here is a soft implementation in php that will be used to validate the structure of the json object that will be sent to the server.


/**
 * 
 * Class Shema
 */
class Schema
{
    private $validationMap = [];
    private $results = [];
    private $schema = [];
    private $hasError = null;
    private $errorResult = null;
    private $isParsed = false;
    private $hasSchema = false;
    private $isProcessed = false;


    public function __construct(array $shema)
    {
        $this->schema = $shema;
        $this->hasSchema = true;
    }

    public function reset()
    {
        $this->validationMap = [];
        $this->results = [];
        $this->hasError = null;
        $this->errorResult = null;
        $this->isParsed = false;
        $this->hasSchema = false;
        $this->isProcessed = false;
    }


    public function getSchema()
    {
        return $this->schema;
    }

    public function getValidationMap()
    {
        return $this->validationMap;
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getHasError()
    {
        return $this->hasError;
    }

    public function getIsParsed()
    {
        return $this->isParsed;
    }

    public function getErrorResult()
    {
        return $this->errorResult;
    }

    public function hasSchema()
    {
        return $this->hasSchema;
    }

    public function getIsProcessed()
    {
        return $this->isProcessed;
    }

    private function processSchema()
    {

        foreach ($this->schema as $key => $value) {
            foreach ($value as $constraint => $constraintValue) {
                $rule = null;
                $type = $value["type"];
                if ($constraint === "type") {
                    switch ($constraintValue) {
                        case "string":
                            $rule = new StringValidator();
                            break;
                        case "double":
                            $rule = new DoubleValidator();
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
                } else if ($constraintValue === true) {
                    switch ($constraint) {
                        case "notBlank":
                            $rule = new NotBlankValidator();
                            break;
                    }
                } else if ($constraint === "range") {
                    switch ($type) {
                        case "integer":
                            $rule = new IntegerRangeValidator($constraintValue);
                            break;
                        case "double":
                            $rule = new IntegerRangeValidator($constraintValue);
                            break;
                        case "array":
                            $rule = new ArrayRangeValidator($constraintValue);
                            break;
                        case "string":
                            $rule = new StringRangeValidator($constraintValue);
                            break;
                    }
                }
                $this->validationMap[$key][] = $rule;
            }
        }
        $this->isProcessed = true;
    }

    public function parse($client_json)
    {
        if (!$this->schema) {
            throw new Exception("Schema not set, use setSchema() method to set schema.");
        }
        $this->processSchema();
        try {
            $results = [];
            $data = json_decode($client_json, true);
            foreach ($this->validationMap as $key => $rules) {
                foreach ($rules as $rule) {
                    $readable = $rule->validate($data[$key], $key)();
                    if ($readable["code"] !== "valid") {
                        $this->results = $readable;
                        $this->hasError = true;
                        $this->errorResult = $readable;
                        throw new ValidatorError($readable);
                    }
                    array_push($results, $readable);
                }
            }
            $this->results = $results;
            return $this;
        } catch (Exception $e) {
            throw $e;
        }
        $this->isParsed = true;
    }

    public function safeParse($client_json)
    {
        if (!$this->schema) {
            throw new Exception("Schema not set, use setSchema() method to set schema.");
        }
        $this->processSchema();
        $results = [];
        $this->errorResult = [];
        $data = json_decode($client_json, true);
        /**
         * 
         * 
         * We are going to loop through the validation map and validate the data
         * 
         * 
         */
        foreach ($this->validationMap as $key => $rules) {
            /**
             * 
             * 
             * 
             * 
             * 
             */
            foreach ($rules as $rule) {
                $readable = $rule->validate($data[$key], $key)();
                if ($readable["code"] !== "valid") {
                    $this->hasError = true;
                    array_push($this->errorResult, $readable);
                }
                array_push($this->results, $readable);
            }
        }
        $this->isParsed = true;
        return $this;
    }
}
