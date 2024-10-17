<?php

require_once 'schema.php';

$json = json_encode([
    "id" => null,
    "name" => "Product",
    "description" => "This is a product",
    "price" => 100.00,
    "quantity" => 10,
    "variants" => ["variant1", "variant2"]
]);

// Example usage
$schema = new Schema();
$productSchema = $schema->addSchema([
    "id" => [
        "type" => "null",
    ],
    "name" => [
        "type" => "string",
        "max" => 255,
    ],
    "description" => [
        "type" => "string",
        "max" => 65000,
    ],
    "price" => [
        "type" => "float",
        "min" => 0,
        "max" => 1000,
    ],
    "quantity" => [
        "type" => "integer",
        "min" => 0,
        "max" => 1000,
    ],
    "variants" => [
        "type" => "array",
        "min" => 1,
        "max" => 10,
    ]
]);


$results = $productSchema->parse($json);
echo "<pre>" . var_export($productSchema, true) . "</pre>";
echo "<pre>" . var_export($results, true) . "</pre>";
