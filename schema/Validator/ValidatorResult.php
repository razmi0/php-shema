<?php

/**
 * Class ValidatorResult
 * 
 * A class that represents the result of a validation.
 * 
 * @property string $code The code of the validation result.
 * @property string $expected The expected value.
 * @property string $received The received value.
 * @property array $path The path to the value that was validated.
 * @property string $message A message that describes the result of the validation.
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
