<?php

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
 * |          The parse() or safeParse() method is called by the consumer  |
 *            with the client json data as parameter                       |
 * |                                                                       |
 * |          2.1) MAPPING RULES                                           |
 * |               The method processShema() is internally called and      |
 * |               an array with all the rules is built                    |
 * |               (property validationMap).                               |
 * |                                                                       |
 * |          2.2) PROCESSING DATA                                         |
 * |               The client data is decoded and tested against the       |
 * |               rules using validate() method from Validator classes    |
 * |               and results are built.                                  |
 * |                                                                       |
 * |       3) RETRIEVING RESULTS                                           |
 * |          The method getResults() is called and the results are        |
 * |          retrieved by the consumer                                    |
 * |_______________________________________________________________________|
 * 
 * 
 **/

require_once 'Validator/ValidatorError.php';
require_once 'Core.php';

use Schema\Template as Template;
use Schema\Core as SchemaCore;
use Schema\Validator\ValidatorError as ValidatorError;
use Schema\Validator\ValidatorResult as ValidatorResult;


/**
 * 
 * An exception can be thrown :
 *     - if the schema is not set before processing.
 *     - if the schema is already parsed and the consumer tries to parse it again.
 *     - if the class consumer use the parse() method and the client data has at least one error.
 *       (note : the class consumer can use safeParse() method to avoid the exception).
 * 
 */

use Exception as Exception;

/**
 * 
 * Class Schema
 * 
 * A schema is a set of rules that the client data must follow. Provided with a valid Template, the Schema class can parse the client data and validate it against the rules explicitly defined in the schema by the consumer.
 * 
 * @package Schema
 * @author Cuesta Thomas
 * @version 1.0
 * 
 * <code>
 * <?php
 * $template = Template::fromArray($arrayOfRules);
 * $schema = new Schema($template);
 * $allResults = $schema->safeParse($clientJson)->getResults();
 * ?>
 * </code>
 * 
 * @todo Decouple json_decode from the class so the input can be any type of php array.
 * 
 * 
 */
class Schema extends SchemaCore
{

    /**
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
     * 
     * 
     * The constructor cast the template to a schema.
     * A schema is nothing else than a valid template.
     * @param Template $template
     * 
     * 
     * 
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
     * 
     * 
     * Builds the validation map from the schema.
     * Insert corrensponding rules for each key in the schema calling the Core class.
     * @todo Extract this method to Core ?
     * 
     * 
     * 
     * 
     * 
     */
    private function processSchema(): void
    {
        // Loop through the valid schema provided by the consumer
        // --
        foreach ($this->schema as $key => $value) {

            // Loop through the rules provided by the consumer
            // --
            foreach ($value as $constraint => $constraintValue) {
                $type = $value["type"];

                // Process the rules and store them in the validationMap
                // --

                // Type rules
                // --
                if ($this->isType($constraint)) {
                    $this->validationMap[$key][] = $this->processTypeRules($constraintValue);
                }

                // Complex rules
                // -- 
                else if ($this->isComplex($constraint)) {
                    $this->validationMap[$key][] = $this->processComplexRules($constraint, $constraintValue);
                }

                // Range rules
                // --
                else if ($this->isRange($constraint)) {
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
