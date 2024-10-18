<?php


namespace Schema\Validator;

/**
 * Class ValidatorResult
 * 
 * A class that represents the result of a validation.
 * 
 */
class ValidatorResult
{
    /**
     * 
     * The code of the validation result.
     * 
     */
    private string $code = "";
    /**
     * 
     * The expected value.
     * 
     * 
     */
    private mixed $expected = "";
    /**
     * 
     * The received value.
     * 
     * 
     */
    private mixed $received = "";
    /**
     * 
     * The path to the value that was validated.
     * 
     * 
     */
    private array $path = [];
    /**
     * 
     * A message that describes the result of the validation.
     * 
     * 
     */
    private string $message = "";

    /**
     * 
     * 
     * Build the result object.
     * 
     * 
     */
    public function __construct($code, $expected, $received, $path, $message)
    {
        $this->code = $code;
        $this->expected = $expected;
        $this->received = $received;
        $this->path = $path;
        $this->message = $message;
    }

    /**
     * 
     * 
     * 
     * Get the readable version of the result object as an array.
     * 
     * 
     * 
     */
    public function getReadable()
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
