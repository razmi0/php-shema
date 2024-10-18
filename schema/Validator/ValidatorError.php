<?php


namespace Schema\Validator;

use Exception;

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
