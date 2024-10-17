<?php



class Console
{

    static function log(mixed $data, $jsonify = true)
    {
        if ($jsonify) {
            echo "<pre>" . (json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
        } else {
            echo "<pre>" .  var_export($data) . "</pre>";
        }
    }
}
