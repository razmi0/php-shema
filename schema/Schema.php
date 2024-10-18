<?php

require_once 'Validator/TypeValidator.php';
require_once 'Validator/RangeValidator.php';
require_once 'Validator/ComplexValidator.php';
require_once 'Validator/ValidatorError.php';

/**
 * 
 * 
 * Grouped use statements ( PHP 7.0 and above )
 * 
 * 
 * 
 */

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
    ValidatorError,
    ValidatorInterface,
    ValidatorResult
};

/**
 * 
 * An exception can be thrown :
 *     - if the schema is not set before processing.
 *     - if the class consumer use the parse method and the client data has at least one error.
 *         (note : the class consumer can use safeParse method to avoid the exception).
 * 
 */

use Exception as Exception;
use Schema\Template\Template;

/**
 * 
 * 
 *  _______________________________________________________________________
 * |                                                                       |
 * |    This class handles the schema validation and processing.           |
 * |                                                                       |
 * |    How it basically works for the consumer :                          |
 * |                                                                       |
 * |       1) INSTANCIATION                                                |
 * |          An instance of Schema is declared and a valid                |
 * |          Template is provided as constructor parameter.               |
 * |                                                                       |
 * |       2) PARSING                                                      |
 * |          The parse() or safeParse() method is called with the         |
 * |          client json data as parameter by the consumer                |
 * |                                                                       |
 * |          2.1) MAPPING RULES                                           |
 * |               The method processShema() is internally called and      |
 * |               an array with all the rules is built                    |
 * |               (property validationMap).                               |
 * |                                                                       |
 * |          2.2) PROCESSING DATA                                         |
 * |               The client data is decoded and tested against the       |
 * |               rules using validate() method from Validator classes    |
 * |               and results are built (property results).               |
 * |                                                                       |
 * |       3) RETRIEVING RESULTS                                           |
 * |          The method getResults() is called and the results are        |
 * |          retrieved by the consumer                                    |
 * |_______________________________________________________________________|
 * 
 * 
 **/



/**
 * 
 * Class Schema
 * 
 * 
 * @author Cuesta Thomas
 * @version 1.0
 * 
 * <code>
 * <?php
 * $schema = new Schema(Template::fromArray($arrayOfRules));
 * $results = $schema->safeParse($clientJson)->getResults();
 * ?>
 * </code>
 * 
 * 
 * 
 */
class Schema
{

    /**
     * 
     * 
     * 
     * Stores all the results of the client data validation against the schema after parsing.
     * It is the final output of the parsing and validation process.
     * ( valid and not valid results )
     * 
     * 
     */
    private ValidatorResult | array $results = [];
    /**
     * 
     * 
     * 
     * Stores only the error results.
     * 
     * 
     * 
     * 
     */
    private ValidatorResult | array $errorResults = [];
    /**
     * 
     * 
     * 
     * 
     * Stores only the success results.
     * 
     * 
     * 
     */
    private ValidatorResult | array $successResults = [];
    /**
     * 
     * 
     * 
     * 
     * Stores the schema definition.
     * 
     * 
     * 
     */
    private array $schema = [];
    /**
     * 
     * 
     * 
     * 
     * Stores all the rules for each key in the schema.
     * Internally managed.
     * The client data will be validated against these rules.
     * @access private
     * 
     * 
     */
    private array $validationMap = [];
    /**
     * 
     * 
     * 
     * 
     * Indicates whether the schema is defined.
     * 
     * 
     * 
     */
    private bool $hasSchema = false;
    /**
     * 
     * 
     * 
     * 
     * Indicates whether the schema has been processed and the validation map has been built.
     * Avoid processing the schema multiple times.
     * Internally managed.
     * @access private
     * 
     * 
     */
    private bool $isProcessed = false;
    /**
     * 
     * 
     * 
     * 
     * Indicates whether the schema has been parsed.
     * 
     * 
     * 
     */
    private bool $isParsed = false;
    /**
     * 
     * 
     * 
     * 
     * Indicates whether there are any errors in the client data.
     * 
     * 
     * 
     */
    private bool $hasError = false;
    /**
     * 
     * The constructor cast the template to a schema.
     * A schema is nothing else than a valid template.
     * @param Template $template
     * 
     */
    public function __construct(Template $template)
    {
        $this->schema = $template->getTemplate();
        $this->hasSchema = true;
    }

    /**
     * 
     * 
     * 
     * 
     * Resets the schema validation and processing but not the schema definition.
     * Use it to reset the validation process and start over with the same schema.
     * Throw an exception if the schema is not set.
     * @throw Exception
     * 
     * 
     */
    public function resetParsing(): void
    {
        if (!$this->hasSchema) {
            throw new Exception("Schema not set, use setSchema() method to set schema.");
        }
        $this->validationMap = [];
        $this->hasError = false;
        $this->results = [];
        $this->errorResults = [];
        $this->successResults = [];
        $this->isParsed = false;
        $this->isProcessed = false;
    }

    // Getters
    // --

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getErrorResults(): array
    {
        return $this->errorResults;
    }

    public function getSuccessResults(): array
    {
        return $this->successResults;
    }

    public function getHasError(): bool
    {
        return $this->hasError;
    }

    public function getIsParsed(): bool
    {
        return $this->isParsed;
    }

    public function getHasSchema(): bool
    {
        return $this->hasSchema;
    }


    /**
     * 
     * 
     * Builds the validation map from the schema.
     * Insert corrensponding rules for each key in the schema.
     * 
     * 
     */
    private function processSchema(): void
    {

        foreach ($this->schema as $key => $value) {
            foreach ($value as $constraint => $constraintValue) {
                $type = $value["type"];
                if ($constraint === "type") {
                    $this->validationMap[$key][] = $this->processTypeRules($constraintValue);
                } else if ($constraintValue === true) {
                    $this->validationMap[$key][] = $this->processComplexRules($constraint);
                } else if ($constraint === "range") {
                    $this->validationMap[$key][] = $this->processRangeRules($constraintValue, $type);
                }
            }
        }
        // Mark the schema as processed
        // --
        $this->isProcessed = true;
    }

    /**
     * 
     * 
     * Type rules are processed here : string, double, integer, array, null
     * 
     * 
     */
    private function processTypeRules($constraintValue): ValidatorInterface
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
    private function processComplexRules($constraint): ValidatorInterface
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
    private function processRangeRules($range, $type): ValidatorInterface
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

    /**
     * 
     * 
     * 
     * 
     * Parse the client data against the validationMap and throw exceptions stopping the execution.
     * @param string $clientJson
     * @throws Exception
     * @throws ValidatorError
     * @return Schema
     * 
     * 
     * 
     * 
     */
    public function parse(string $clientJson): Schema
    {
        // Check if the schema is set
        // --
        if (!$this->schema) {
            throw new Exception("Schema not set, use setSchema() method to set schema.");
        }

        // Check if the schema has been processed
        // --
        if ($this->isProcessed || $this->isParsed) {
            throw new Exception("The schema has already been parsed, use reset() method to reset all processing.");
        }

        // Process the schema to build the validation map
        // --
        $this->processSchema();

        try {
            // Decode the client data
            // --
            $clientData = json_decode($clientJson, true);

            // Loop through the keys provided in the json data 
            // The validationMap keys and the client data keys match
            // --
            foreach ($this->validationMap as $key => $rules) {

                // Loop through the rules for each key
                // --
                foreach ($rules as $rule) {

                    // Validate the data against the rule and store the result as a ValidatorResult object
                    // --
                    $validated = $rule->validate($clientData[$key], $key);

                    // Call the getter getReadable() from ValidatorResult to get a clean result
                    // --
                    $readable = $validated->getReadable();

                    // The parse method doesn't allow errors from the client data 
                    // If the result is not valid, throw an exception
                    // --
                    if ($readable["code"] !== "valid") {

                        // Set the hasError property to true
                        // --
                        $this->hasError = true;

                        // Store the error result
                        // --
                        $this->errorResults = $readable;

                        // Throw a ValidatorError exception with the last readable result as data property
                        // --
                        throw new ValidatorError($readable);
                    }
                    // If the result is valid, store the success result
                    // -- 
                    else {

                        // Store the success result
                        // --
                        $this->successResults[] = $readable;
                    }

                    // Store the result
                    // --
                    $this->results[] = $readable;
                }
            }
        } catch (Exception $e) {
            // If an exception ValidatorError is thrown, rethrow it
            throw $e;
        }
        // Mark the schema as parsed
        // --
        $this->isParsed = true;

        // Return the instance so the consumer can chain methods
        // --
        return $this;
    }



    /**
     * 
     * 
     * 
     * 
     * Parse the client data against the validationMap but doesn't throw exceptions when an error in client data is found so the consumer can handle the errors.
     * @param string $clientJson
     * @throws Exception
     * @return Schema
     * 
     * 
     * 
     * 
     */
    public function safeParse($clientJson): Schema
    {
        // Check if the schema is set
        // --
        if (!$this->schema) {
            throw new Exception("Schema not set, use setSchema() method to set schema.");
        }


        // Check if the schema has been processed
        // --
        if ($this->isParsed) {
            throw new Exception("The schema has already been parsed, use reset() method to reset parsing.");
        }

        // Process the schema to build the validation map
        // --
        $this->processSchema();

        // Decode the client data
        // --
        $data = json_decode($clientJson, true);


        // Loop through the keys provided in the json data 
        // The validationMap keys and the client data keys match
        // --
        foreach ($this->validationMap as $key => $rules) {


            // Loop through the rules for each key
            // --
            foreach ($rules as $rule) {
                // Validate the data against the rule and store the result as a ValidatorResult object
                // --
                $validated = $rule->validate($data[$key], $key);

                // Call the getter getReadable() from ValidatorResult to get a clean result
                // --
                $readable = $validated->getReadable();

                // If the result is not valid,
                // --
                if ($readable["code"] !== "valid") {


                    // Set the hasError property to true
                    // --
                    $this->hasError = true;

                    // Store the error result
                    // --
                    $this->errorResults[] = $readable;
                }
                // If the result is valid,
                // --
                else {


                    // Store the success result
                    // --
                    $this->successResults[] = $readable;
                }

                // Store the result
                // --
                $this->results[] = $readable;
            }
        }

        // Mark the schema as parsed
        // --
        $this->isParsed = true;

        // Return the instance so the consumer can chain methods
        // --
        return $this;
    }
}
