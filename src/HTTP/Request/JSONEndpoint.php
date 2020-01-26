<?php

namespace Sowe\Framework\HTTP\Request;

use Sowe\Framework\HTTP\Request;

abstract class JSONEndpoint extends Request
{
    protected $response;

    public function __construct()
    {
        parent::__construct();
        $this->response = ["status" => true];
        parent::handle();
    }

    public function answer()
    {
        if (in_array("text/html", $this->responseFormats)) {
            $response = "<pre>". json_encode($this->response, JSON_PRETTY_PRINT) . "</pre>";
            header("Content-type: text/html");
        } else {
            $response = json_encode($this->response);
            header("Content-type: application/json");
        }
        
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Length: ". strlen($response));
        echo $response;
        exit;
    }

    public function throw_error($msg)
    {
        $this->response = [
            "status" => false,
            "message" => $msg
        ];
        $this->answer();
    }
}
