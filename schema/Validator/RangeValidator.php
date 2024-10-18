<?php

/**
 * 
 *  _______________________________________________________________________
 * |                                                                       |
 * |        This file contain all ranges rules validator. Each validator   |
 * |        extends from RangeValidator and inherit the range analysis     |
 * |        logic. They implements the ValidatorInterface with the         |
 * |        validator method.                                              |
 * |_______________________________________________________________________|
 * 
 * 
 * 
 **/

namespace Schema\Validator;

require_once 'ValidatorInterface.php';
require_once 'ValidatorResult.php';

use Schema\Validator\ValidatorResult as ValidatorResult;
use Schema\Validator\ValidatorInterface as ValidatorInterface;


/**
 * 
 * 
 * class RangeValidator
 * 
 * A class that represents the range validation logic.
 * 
 * 
 */
class RangeValidator
{
    /**
     * 
     * 
     * The minimum value of the range.
     * 
     * 
     */
    protected int | null $min = null;
    /**
     * 
     * 
     * The maximum value of the range.
     * 
     * 
     */
    protected int | null $max = null;

    /**
     * 
     * 
     * A flag that indicates if the range has a minimum value.
     * 
     * 
     */
    protected bool $hasMin = false;

    /**
     * 
     * 
     * A flag that indicates if the range has a maximum value.
     * 
     * 
     */
    protected bool $hasMax = false;

    protected function __construct(array $range)
    {
        $this->min = $range[0];
        $this->max = $range[1];
        $this->hasMin = isset($range[0]);
        $this->hasMax = isset($range[1]);
    }


    // Methods to check the range type so childs validator apply the correct logic
    // --

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

    /**
     * 
     * 
     * Out of range result object configuration.
     * 
     * 
     */
    private function outOfRangeResult(mixed $value, string $key): ValidatorResult
    {
        return new ValidatorResult("out_of_range", "between " . (($this->min ?? "-infinite") . " and " . ($this->max ?? "+infinite")), $value, [$key], "Value is out of range");
    }

    /**
     * 
     * 
     * Within range result object configuration.
     * 
     * 
     * 
     */
    private function withinRangeResult(mixed $value, string $key): ValidatorResult
    {
        return new ValidatorResult("valid", "between " . (($this->min ?? "-infinite") . " and " . ($this->max ?? "+infinite")), $value, [$key], "Value is within range");
    }

    /**
     * 
     * 
     * No range result object configuration.
     * 
     * 
     * 
     */
    protected function noRangeResult(mixed $value, string $key): ValidatorResult
    {
        return new ValidatorResult("valid", "no_range", $value, [$key], "No range specified");
    }

    /**
     * 
     * 
     * Evaluate the range and return the correct result object.
     * 
     * 
     */
    protected function evaluateRange(mixed $value, string $key, bool $condition): ValidatorResult
    {
        return $condition ? $this->outOfRangeResult($value, $key) : $this->withinRangeResult($value, $key);
    }
}

/**
 * 
 * 
 * class IntegerRangeValidator
 * 
 * 
 * A class that represents the integer range validation logic.
 * 
 * 
 */
class IntegerRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct(array $range)
    {
        parent::__construct($range);
    }


    /**
     * 
     * 
     * An integer is in range if it is greater than or equal to the minimum value and less than or equal to the maximum value.
     * 
     * 
     */
    public function validate(mixed $value, string $key): ValidatorResult
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

/**
 * 
 * 
 * class ArrayRangeValidator
 * 
 * 
 * A class that represents the array range validation logic.
 * 
 * 
 */
class ArrayRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct($range)
    {
        parent::__construct($range);
    }

    /**
     * 
     * 
     * An array is in range if it has a number of elements greater than or equal to the minimum value and less than or equal to the maximum value.
     * 
     * 
     * 
     */
    public function validate(mixed $value, string $key): ValidatorResult
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

/**
 * 
 * 
 * class StringRangeValidator
 * 
 * 
 * A class that represents the string range validation logic.
 * 
 * 
 */
class StringRangeValidator extends RangeValidator implements ValidatorInterface
{

    public function __construct($range)
    {
        parent::__construct($range);
    }

    /**
     * 
     * 
     * A string is in range if it has a number of characters greater than or equal to the minimum value and less than or equal to the maximum value.
     * 
     * 
     */
    public function validate(mixed $value, string $key): ValidatorResult
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
