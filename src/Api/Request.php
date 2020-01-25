<?php

namespace Sowe\Framework\Api;

abstract class Request
{
    protected $variables;
    protected $response;
    protected $ip;
    protected $method;

    protected $requestFormat;
    protected $responseFormat;

    protected function __construct()
    {
        $this->variables = [];
        $this->response = ["status" => true];
        $this->ip = $this->get_ip();

        $this->get_http_method();

        $this->get_accept();
        $this->get_content_type();

        $this->collect_variables();
    }

    /** Content-Type **/
    private function get_content_type()
    {
        $headers = apache_request_headers();
        $content_type = $headers['Content-Type'] ?? "application/json";
        
        switch ($content_type) {
            case "application/json":
                $this->requestFormat = "json";
                break;
            default:
                $this->throw_error("Unsupported Content-Type '". $content_type . "'");
        }
    }

    /** Accept */
    private function get_accept()
    {
        $headers = apache_request_headers();
        $accept = $headers['Accept'] ?? "application/json";

        $accept = call_user_func_array('array_merge', array_map(function ($items) {
            $items = explode(",", $items);
            // Filtering invalid mimes
            return array_filter($items, function ($item) {
                return strpos($item, "/") > 0;
            });
        }, explode(";", $accept)));
            
        foreach ($accept as $mime) {
            switch ($mime) {
                case "application/json":
                case "*/*":
                    $this->responseFormat = "json";
                    return;
                default:
                    break;
            }
        }

        $this->responseFormat = "json";
        $this->throw_error("No supported Accept MIME found: ". implode(", ", $accept));
    }


    /** Collect variables **/
    private function collect_variables()
    {
        switch ($this->responseFormat) {
            case "json":
                $json = file_get_contents('php://input');
        
                if (!empty($json)) {
                    $variables = @json_decode($json, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->throw_error("Invalid JSON format");
                    }
        
                    $this->variables = array_merge($this->variables, $variables);
                }
                break;
            default:
                break;
        }
    }

    /** Get HTTP method (post,get,put...) **/
    private function get_http_method()
    {
        $this->method = strtolower($_SERVER['REQUEST_METHOD']);
    }

    /** Get Origin IP */
    private function get_ip()
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return "Unknown";
    }

    /** Will be called by child */
    protected function handle()
    {
        if (!method_exists($this, $this->method)) {
            $this->throw_error("Invalid HTTP Method");
        }
        
        $this->validate_variables();
        
        if (!isset($this->error) || $this->error !== true) {
            call_user_func(array($this, $this->method));
        }

        $this->answer();
    }

    /** Validations **/
    abstract protected function validate_variables();

    protected function static_validation(&$variable, $type)
    {
        switch ($type) {
            case "float":
                if (is_numeric($variable)) {
                    $variable = floatval($variable);
                    return is_float($variable);
                }
                // no break
            case "int":
                if (is_numeric($variable)) {
                    $variable = intval($variable);
                    return is_float($variable);
                }
                // no break
            case "bool":
                switch ($variable) {
                    case true:
                    case "true":
                    case "1":
                        $variable = true;
                        break;
                    case false:
                    case "false":
                    case "0":
                        $variable = false;
                        break;
                    default:
                        break;
                }
                return is_bool($variable);
            case "string":
                return is_string($variable);
            case "array":
                return is_array($variable);
            case "function":
            case "callable":
                return is_callable($variable);
            default:
                throw new \Exception("Unknown validation '". $validation ."'");
        }
    }

    protected function validate_variable($name, $required = true, $validation = null, $default = null)
    {
        if ($required && !isset($this->variables[$name])) {
            throw new \Exception("Missing required ". $name ." variable");
        }

        if (isset($this->variables[$name]) && $validation !== null) {
            if (is_string($validation)) {
                if (!$this->static_validation($this->variables[$name], $validation)) {
                    $this->throw_error("'" . $name . "' must be '"+ $validation +"'");
                }
            } elseif (is_callable($validation)) {
                if (!$validation($this->variables[$name])) {
                    $this->throw_error("Incorrect '" . $name . "' format or type");
                }
            } elseif ($validation !== null) {
                throw new \Exception("Unexpected validation for '". $name ."' variable");
            }
        }

        if ($default !== null && !isset($this->variables[$name])) {
            $this->variables[$name] = $default;
        }
    }

    /** Response **/
    protected function answer()
    {
        switch ($this->responseFormat) {
            case "json":
                $json = json_encode($this->response);
                header("Content-type: application/json");
                header("Cache-Control: no-cache, must-revalidate");
                header("Content-Length: ". strlen($json));
                print $json;
                break;
            default:
                break;
        }
        exit;
    }

    protected function throw_error($msg)
    {
        $this->response = [
            "status" => false,
            "msg" => $msg
        ];
        $this->answer();
    }
}
