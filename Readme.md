# Schema

A schema is a representation of a model. It contains appropriate rules to test a json data and to validate it. It can be used to validate json before saving it to database.

## Usage

To use a shema we first need to build a Template Object. The Template Object will valid the shema :

```php

use Schema\Template as Template;

$untrustedSchema = [
    "id" => ["type" => 'null'],
    'name' => ["type" => 'string'],
    'price' => ["type" => 'int', "range" => [0, 100]],
    'description' => ["type" => 'string', "range" => [0, 100], "regex" => '/^[a-zA-Z0-9 ]+$/'],
    "variants" => [ "type" => 'array', "range" => [1, 5]]
];

```

Now we can test the schema :

```php

$trustedSchema = Template::fromArray($untrustedSchema);

```

If no error has been thrown, the schema is well formed. Let's declare and initialize it :

```php

$schema = new Schema($trustedSchema);

```

Then we can parse the json data and get all the results :

```php

$client_json = json_encode([
    "id" => null,
    'name' => "Product 1",
    'price' => 10,
    'description' => "This is a product",
    "variants" => [
        "variant1",
        "variant2"
    ]
]);

$results = $schema->safeParse($client_json)->getResults();

```

If we want to grab more information about errors / success in the client data, we use can dedicated getters :

```php

$success = $schema->getSuccessResults();
$errors = $schema->getErrorResults();

$isParsed = $schema->getIsParsed();
$hasError = $schema->getHasError();

```

> **Important note** : The method _safeParse_ and _parse_ share a similar implementation **BUT** _parse_ will throw an exception if the json data is not valid. _safeParse_ will not throw an exception but will set the error flag to true.

The results are stored in a PHP array, that look like this :

```php

Array
(
    [0] => Array
        (
            [code] => valid
            [expected] => between 0 and 65
            [received] => 9
            [path] => Array
                (
                    [0] => name
                )

            [message] => Value is within range
        )

    [1] => Array
        (
            [code] => invalid_pattern
            [expected] => /^[a-zA-Z]+$/
            [received] => Product 1
            [path] => Array
                (
                    [0] => name
                )

            [message] => Value does not match pattern
        )

```

or in json format :

```json
[
  {
    "code": "valid",
    "expected": "between 0 and 65",
    "received": 9,
    "path": ["name"],
    "message": "Value is within range"
  },
  {
    "code": "invalid_pattern",
    "expected": "/^[a-zA-Z]+$/",
    "received": "Product 1",
    "path": ["name"],
    "message": "Value does not match pattern"
  }
]
```

Do whatever you want with the results.

## Constraints available

- Type constraints :
  - string
  - int
  - float
  - bool
  - array
  - null
- Range constraints :
  - range[min, max]
- Complex constraints :
  - regex
  - notBlank

## Exemple

Go to [exemple](http://localhost/shema_test/exemple/index.php) to see the exemple.

## Todo

- [ ] Decouple json_decode from the schema so the consumer can test an array of data
- [ ] Add optional values
- [ ] Add multiple types
