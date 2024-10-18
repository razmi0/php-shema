<?php

require_once 'schema/Schema.php';
require_once 'Page.php';


new Page();

$json = json_encode([
    "id" => null,
    "name" => "Product",
    "description" => "This is a product",
    "price" => 100.15,
    "quantity" => 10,
    "variants" => ["variant1", "variant2"]
]);

// Example usage
//--
$productSchema = [
    "id" => ["type" => "null"],
    "name" => ["type" => "string", "range" => [0, 65]],
    "description" => ["type" => "string", "range" => [10, 65000]],
    "price" => ["type" => "double", "range" => [0.01, null]],
    "quantity" => ["type" => "integer", "range" => [0, 1000]],
    "variants" => ["type" => "array", "range" => [1, null]]
];

$schema = new Schema($productSchema);
$results = $schema->safeParse($json)->getResults();

$hasSchema = $schema->hasSchema();
$isProcessed = $schema->getIsProcessed();
$isParsed = $schema->getIsParsed();
$hasError = $schema->getHasError();
$errorResult = $schema->getErrorResult();
$validationMap = $schema->getValidationMap();

Page::write("hasError: ");
Page::write($hasError);
Page::write("results: ");
Page::write($results);
Page::write("valMap: ");
Page::write($validationMap, false);
