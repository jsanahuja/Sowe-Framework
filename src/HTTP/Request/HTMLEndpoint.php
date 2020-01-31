<?php

namespace Sowe\Framework\HTTP\Request;

use Sowe\Framework\HTTP\Request;

abstract class HTMLEndpoint extends Request
{
    protected $file;
    protected $replaces;

    public function __construct()
    {
        parent::__construct();
        $this->replaces = [];
    }

    protected function parse_file($content, $variables, $format = "/%%%([^\ ]+)%%%/")
    {
        preg_match_all($format, $content, $matches);

        foreach ($matches[1] as &$match) {
            if(!isset($variables[strtolower($match)])){
                throw new \Exception("Undefined '". strtolower($match) ."' replace");
            }
            $match = $variables[strtolower($match)];
        }

        return str_replace($matches[0], $matches[1], $content);
    }

    public function answer()
    {
        if (!file_exists($this->file)) {
            throw new \Exception("File '". $this->file ."' not found");
        }

        $response = $this->parse_file(file_get_contents($this->file), $this->replaces);

        header("Content-type: text/html");
        header("Content-Length: ". strlen($response));
        echo $response;
        exit;
    }

    protected function throw_error($code, $arguments=[]){
        http_response_code($code);
        /**
         * This will have to be handled by our child
         * or the Router class.
         */
        throw new \Exception($msg);
    }
}
