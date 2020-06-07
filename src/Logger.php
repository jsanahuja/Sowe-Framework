<?php

namespace Sowe\Framework;

use Monolog\Logger as MonoLogger;
use Psr\Log\LoggerInterface;

class Logger extends MonoLogger implements LoggerInterface{

    private function prepend_context($message){
        $backtrace = debug_backtrace();
        $parts = explode("/", $backtrace[1]["file"]);
        return end($parts) .
            ":" . $backtrace[2]["function"] .
            ":" . $backtrace[1]["line"] . ": " . $msg;
    }

    public function emergency($message, array $context = array()){
        parent::emergency($this->prepend_context($message), $context);
    }

    public function alert($message, array $context = array()){
        parent::alert($this->prepend_context($message), $context);
    }

    public function critical($message, array $context = array()){
        parent::critical($this->prepend_context($message), $context);
    }

    public function error($message, array $context = array()){
        parent::error($this->prepend_context($message), $context);
    }

    public function warning($message, array $context = array()){
        parent::warning($this->prepend_context($message), $context);
    }

    public function notice($message, array $context = array()){
        parent::notice($this->prepend_context($message), $context);
    }

    public function info($message, array $context = array()){
        parent::info($this->prepend_context($message), $context);
    }

    public function debug($message, array $context = array()){
        parent::debug($this->prepend_context($message), $context);
    }

    public function log($level, $message, array $context = array()){
        parent::log($level, $this->prepend_context($message), $context);
    }

}
