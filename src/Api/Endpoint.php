<?php

namespace Sowe\Framework\Api;

use \Sowe\Framework\Api\Request;

class Endpoint extends Request
{
    protected $database;

    public function __construct()
    {
        parent::__construct();
        parent::handle();
    }

    protected function validate_variables()
    {
    }
}
