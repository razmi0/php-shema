<?php

namespace Schema\Validator;

interface ValidatorInterface
{
    public function validate($value, $key);
}
