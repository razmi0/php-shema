<?php



class Page
{

    public function __construct()
    {
        echo "<html data-theme=dark>
                <head>
                    <title>Schema Validation</title>
                    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css'>
                </head>
                <body>
                    <header>
                        <h1 style='width : 100%; text-align : center;'>Schema Validation</h1>
                    </header>
                    <main class='container-fluid'>
        ";
    }

    static function write(mixed $data, $jsonify = true)
    {
        if ($jsonify) {
            echo "<pre>" . (json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
        } else {
            $asStr = print_r($data, true);
            echo "<pre>" .  $asStr . "</pre>";
        }
    }
}
