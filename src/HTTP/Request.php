<?php

namespace Sowe\Framework\HTTP;

abstract class Request
{
    protected $variables;
    protected $ip;

    protected $method;
    protected $responseFormats;


    public function __construct()
    {
        $this->variables = [];

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
            $this->throw_error("Unsupported HTTP Method");
        }
        
        $headers = apache_request_headers();

        // Parsing variables
        $content_type = isset($headers['Content-Type']) ? explode(";", $headers['Content-Type'])[0] : "";
        switch ($content_type) {
            case "application/x-www-form-urlencoded":
                $this->variables = array_merge($this->variables, $_POST);
                break;
            case "multipart\/form-data":
                $this->variables = array_merge($this->variables, $_POST);
                $this->variables = array_merge($this->variables, $_FILES);
                break;
            case "application/json":
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
        $this->variables = array_merge($this->variables, $_GET);

        // Parsing expected response formats
        if (isset($headers['Accept'])) {
            $this->responseFormats = call_user_func_array('array_merge', array_map(function ($items) {
                $items = explode(",", $items);
                // Filtering invalid mimes
                return array_filter($items, function ($item) {
                    return strpos($item, "/") > 0;
                });
            }, explode(";", $headers['Accept'])));
        } else {
            $this->responseFormats = [];
        }
    }

    abstract protected function answer();

    abstract protected function throw_error($msg);

    protected function handle()
    {
        call_user_func(array($this, $this->method));
        $this->answer();
    }

    /** Validations */
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
                    return is_int($variable);
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
            case "email":
                return filter_var($variable, FILTER_VALIDATE_EMAIL);
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
                    $this->throw_error("The variable '" . $name . "' must be a valid '". $validation ."'");
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
}
