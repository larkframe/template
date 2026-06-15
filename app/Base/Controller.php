<?php

namespace App\Base;

class Controller
{
    protected function __construct() {

    }

    protected function api($data, $code = 200, $message = "")
    {
        if ($code == 200) {
            return json([
                'code' => $code,
                'message' => $message,
                'data' => $data
            ]);
        } else {
            return json([
                'code' => $code,
                'message' => $message,
                'data' => []
            ]);
        }
    }
}