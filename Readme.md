# Validator

This document describes the Validator class and its use for validating data against a defined schema.

## Introduction

The Validator class offers a robust mechanism for validating data based on a set of rules and constraints defined in a schema. It allows verifying various aspects of the data, including:

- Presence of required fields
- Nullability of fields
- Data types
- Numeric ranges
- String and array lengths
- Regular expression matching
- Limitation of the number of keys

### Supported data types

The validator supports the validation of the following data types:

- string
- integer
- double
- boolean
- Typed arrays: string[], integer[], double[], boolean[], array[], object[], any[]
- object
- any (corresponds to any data type)

### Using the validator

1. Initialization
   The use of the validator begins with its initialization with a schema. The schema, represented as an associative array, defines the validation rules for each field of the data to be validated.

   Example schema:

```php
$schema = [
   "name" => [
      "type" => "string",
      "required" => true, // Name is required
      "length" => [0, 20] // Minimum and maximum length of the name
   ],

   "age" => [
      "type" => "integer",
      "required" => false, // Age is not required
      "range" => [13, 120], // Valid range of age values
      "nullable" => true // Age can be null
   ],

   "email" => [
      "type" => "string",
      "required" => true,
      "regex" => "/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/" // Regular expression to validate email format
   ]

    "numbers" => [
        "type" => "integer[]",
        "length" => [0, 10] // Maximum length of the array
    ],
];

$validator = new Validator($schema);

```

2. Data validation

   After initialization, the `safeParse()` method is used to validate a set of data against the schema. This method takes an associative array of data as input and returns the Validator object itself, allowing method chaining.
   Example usage:

```php
$data = [
   "name" => "John Doe",
   "age" => 30,
   "email" => "john.doe@example.com"
   "numbers" => [1, 2, 3]
];

$validator->safeParse($data);
```

3. Accessing validation results
   The Validator object offers methods to access the validation results:

- `getResults()`: Returns an array containing all validation results, including errors and successes.
- `getErrors()`: Returns an array containing only validation errors.
- `getValids()`: Returns an array containing valid fields.
- `getIsValid()`: Returns a boolean indicating whether the overall validation was successful (i.e., no errors were encountered).

Example usage:

```php
if ($validator->getIsValid()) {
   // The data is valid
} else {
   // The data is invalid
   $errors = $validator->getErrors();
   // Handle errors...
}
```

### Advanced features

Key limiter

The validator allows limiting the number of keys allowed in the validated data. By default, the number of keys is limited to the number of keys defined in the schema. This limitation can be disabled using the `setKeyLimiter()` method with the argument `false`.

Example usage:

```php
// Disable the key limiter
$validator->setKeyLimiter(false);
```

### ValidatorResult

The ValidatorResult class is used internally by the validator to represent the result of an individual validation. Each ValidatorResult object contains the following information:

- `code`: Validation result code.
- `expected`: Expected value.
- `received`: Received value.
- `path`: Path to the validated value (as an array, e.g., `["field1", 0]` for the first element of an array named "field1").
- `message`: Message describing the validation result.

Exemple of an invalid result:

```php
array (
  0 =>
  array (
    'code' => 'invalid_type',
    'expected' => 'string',
    'received' => 'integer',
    'path' =>
    array (
      0 => 'name',
    ),
    'message' => 'Invalid type',
  ),
)
```

A very good use case is to send a json response to the client with the errors.

```json
[
  {
    "code": "invalid_type",
    "expected": "string",
    "received": "integer",
    "path": ["name"],
    "message": "Invalid type"
  }
]
```
