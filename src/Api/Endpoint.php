<?php

namespace Sowe\Framework\Api;

use \Sowe\Framework\Database;
use \Sowe\Framework\Api\Request;

class Endpoint extends Request
{
    protected $database;
    protected $argv;

    public function __construct(Database $database, ...$argv)
    {
        parent::__construct();
        $this->database = $database;
        $this->argv = $argv;
        parent::handle();
    }

    protected function validate_variables()
    {
    }
}
