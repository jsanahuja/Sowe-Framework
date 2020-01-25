<?php

namespace Sowe\Framework\Api;

use \Sowe\Framework\Api\Request;

class Endpoint extends Request
{
    protected $database;

    public function __construct(Database $database)
    {
        parent::__construct();
        $this->database = $database;
        parent::handle();
    }

    protected function validate_variables()
    {
    }
}
