<?php


namespace Schema\Validator;

use Exception;

/**
 * 
 * Class ValidatorError
 * 
 * Extends the Exception class to provide a custom error message coming from the Validator result object and a getter to access the error result object.
 * 
 */
class ValidatorError extends Exception
{
    private $data;
    public function __construct(array $data)
    {
        parent::__construct($data["message"], 500);
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
