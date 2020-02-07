<?php

namespace Sowe\Framework\HTTP;

abstract class Request
{
    protected $variables;
    protected $ip;
    
    protected $headers;
    protected $body;
    protected $method;
    protected $responseFormats;


    public function __construct()
    {
        $this->variables = [];
        $this->responseFormats = [];

        $this->get_ip();
        $this->parse_request();
    }

    /** Get Origin IP */
    private function get_ip()
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $this->ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
            $this->ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            $this->ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $this->ip = "Unknown";
        }
    }

    /** Parse HTTP request */
    private function parse_request()
    {
        $this->method = strtolower($_SERVER['REQUEST_METHOD']);
        
        if (!method_exists($this, $this->method)) {
            $this->throw_error("405");
        }
        
        $this->headers = array_change_key_case(apache_request_headers(), CASE_LOWER);

        // Parsing variables
        $content_type = isset($this->headers['content-type']) ? explode(";", $this->headers['content-type'])[0] : "";
        
        // Get variables have the lowest priority
        $this->variables = array_merge($this->variables, $_GET);

        $this->body = file_get_contents('php://input');
        switch ($content_type) {
            case "application/x-www-form-urlencoded":
                $this->variables = array_merge($this->variables, $_POST);
                break;
            case "multipart\/form-data":
                $this->variables = array_merge($this->variables, $_POST);
                $this->variables = array_merge($this->variables, $_FILES);
                break;
            case "application/json":
                if (!empty($this->body)) {
                    $data = @json_decode($this->body, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->throw_error("400");
                    }
        
                    $this->variables = array_merge($this->variables, $data);
                }
                break;
            default:
                break;
        }

        // Parsing expected response formats
        if (isset($this->headers['accept'])) {
            $this->responseFormats = call_user_func_array('array_merge', array_map(function ($items) {
                $items = explode(",", $items);
                // Filtering invalid mimes
                return array_filter($items, function ($item) {
                    return strpos($item, "/") > 0;
                });
            }, explode(";", $this->headers['accept'])));
        }
    }

    abstract protected function answer();

    abstract protected function throw_error($code, $arguments=[]);

    protected function handle()
    {
        call_user_func(array($this, $this->method));
        $this->answer();
    }

}
