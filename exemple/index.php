<?php

require_once '../schema/Schema.php';
require_once '../schema/Template.php';
require_once 'Page.php';

use Schema\Template as Template;

$client_json = json_encode([
    "id" => 1,
    "name" => "Product 1",
    "description" => "This is a product",
    "price" => 100.15,
    "quantity" => "10",
    "variants" => ["variant1", "variant2"]
]);

// Example usage
//--

$productTemplate = Template::fromArray([
    "id" => ["type" => "null"],
    "name" => ["type" => "string", "range" => [0, 65], "regex" => "/^[a-zA-Z]+$/"],
    "description" => ["type" => "string", "range" => [10, 65000]],
    "price" => ["type" => "double", "range" => [0.01, null]],
    "quantity" => ["type" => "integer", "range" => [0, 1000]],
    "variants" => ["type" => "array", "range" => [1, null]]
]);

$schema = new Schema($productTemplate);

$results = $schema->safeParse($client_json)->getResults();
$errorResult = $schema->getErrorResults();
$successResult = $schema->getSuccessResults();
$isParsed = $schema->getIsParsed();
$hasError = $schema->getHasError();

new Page();
Page::write($results);
