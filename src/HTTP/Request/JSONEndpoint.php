<?php

namespace Sowe\Framework\HTTP\Request;

use Sowe\Framework\HTTP\Request;

abstract class JSONEndpoint extends Request
{
    protected $response;

    public function __construct()
    {
        parent::__construct();
        $this->response = [];
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

    protected function throw_error($code, $arguments=[]){
        http_response_code($code);
        $this->response = array_merge($this->response, $arguments);
        $this->answer();
    }
}
