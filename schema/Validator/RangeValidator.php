<?php

namespace Schema\Validator;

require_once 'ValidatorInterface.php';
require_once 'ValidatorResult.php';

use Schema\Validator\ValidatorResult as ValidatorResult;
use Schema\Validator\ValidatorInterface as ValidatorInterface;


class RangeValidator
{
    protected $min = null;
    protected $max = null;
    protected $hasMin = false;
    protected $hasMax = false;

    protected function __construct($range)
    {
        $this->min = $range[0];
        $this->max = $range[1];
        $this->hasMin = isset($range[0]);
        $this->hasMax = isset($range[1]);
    }

    protected function hasRange()
    {
        return $this->hasMin && $this->hasMax;
    }

    protected function onlyMin()
    {
        return $this->hasMin && !$this->hasMax;
    }

    protected function onlyMax()
    {
        return $this->hasMax && !$this->hasMin;
    }

    protected function noRange()
    {
        return !$this->hasMin && !$this->hasMax;
    }

    private function outOfRangeResult($value, $key)
    {
        return new ValidatorResult("out_of_range", "between " . (($this->min ?? "-infinite") . " and " . ($this->max ?? "+infinite")), $value, [$key], "Value is out of range");
    }

    private function withinRangeResult($value, $key)
    {
        return new ValidatorResult("valid", "between " . (($this->min ?? "-infinite") . " and " . ($this->max ?? "+infinite")), $value, [$key], "Value is within range");
    }

    protected function noRangeResult($value, $key)
    {
        return new ValidatorResult("valid", "no_range", $value, [$key], "No range specified");
    }

    protected function evaluateRange($value, $key, bool $condition)
    {
        return $condition ? $this->outOfRangeResult($value, $key) : $this->withinRangeResult($value, $key);
    }
}


class IntegerRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct($range)
    {
        parent::__construct($range);
    }


    public function validate($value, $key)
    {
        if ($this->hasRange()) {
            $condition = $value < $this->min || $value > $this->max;
            return $this->evaluateRange($value, $key, $condition);
        } else if ($this->onlyMin()) {
            $condition = $value < $this->min;
            return $this->evaluateRange($value, $key, $condition);
        } else if ($this->onlyMax()) {
            $condition = $value > $this->max;
            return $this->evaluateRange($value, $key, $condition);
        } else if ($this->noRange()) {
            return $this->noRangeResult($value, $key);
        }
    }
}

class ArrayRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct($range)
    {
        parent::__construct($range);
    }

    public function validate($value, $key)
    {
        $count = count($value);

        if ($this->hasRange()) {
            $condition = $count < $this->min || $count > $this->max;
            return $this->evaluateRange($count, $key, $condition);
        } else if ($this->hasMin) {
            $condition = $count < $this->min;
            return $this->evaluateRange($count, $key, $condition);
        } else if ($this->hasMax) {
            $condition = $count > $this->max;
            return $this->evaluateRange($count, $key, $condition);
        } else if ($this->noRange()) {
            return $this->noRangeResult($count, $key);
        }
    }
}

class StringRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct($range)
    {
        parent::__construct($range);
    }

    public function validate($value, $key)
    {
        $size = strlen($value);

        if ($this->hasRange()) {
            $condition = $size < $this->min || $size > $this->max;
            return $this->evaluateRange($size, $key, $condition);
        } else if ($this->hasMin) {
            $condition = $size < $this->min;
            return $this->evaluateRange($size, $key, $condition);
        } else if ($this->hasMax) {
            $condition = $size > $this->max;
            return $this->evaluateRange($size, $key, $condition);
        } else if ($this->noRange()) {
            return $this->noRangeResult($size, $key);
        }
    }
}
