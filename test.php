<?php




$init_data = [
    'badOneString' => '<a></a>',
    'goodOneString' => 'test',
    'one_string_arr' => [
        'test1',
        '<a>test</a>',
        "deeper_stirng_arr" => [
            00019.7,
            '<a>test</a>',
            "deeper_stirng_arr" => [
                'test1',
                '<a></a>',
            ]
        ],
    ],
];

$config = [
    "sanitize" => [
        "html",
        "integer",
        "float"

    ]
];

// $init_data = start($init_data);


// start($init_data);



$sanitized_data = sanitize($init_data, $config);

print_r($sanitized_data);


// strip_tags() FILTER_SANITIZE_NUMBER_FLOAT FILTER_SANITIZE_NUMBER_INT FILTER_SANITIZE_URL


function sanitize($data, array $config)
{
    $rules = $config['sanitize'];

    // Recursive function to sanitize data depending on the rules set in config and the data type
    // use keyword is used to inject parent scope variables into the function closure scope
    // the "&" before $rules and $sanitize_recursively is used to pass the variables by reference ( avoid copying the variables )
    $sanitize_recursively = function ($data) use (&$rules, &$sanitize_recursively) {


        switch (gettype($data)) {
            case 'array':
                return array_map($sanitize_recursively, $data);
                break;

            case 'string':
                if (in_array('html', $rules))
                    return trim(strip_tags($data));
                break;

            case 'integer':
                if (in_array('integer', $rules))
                    return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
                break;

            case 'double': // 'double' is the type returned for floats in PHP
                if (in_array('float', $rules))
                    return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                break;
        }

        return $data;
    };

    return $sanitize_recursively($data);
}
