<?php

namespace Sowe\Framework;

use Sowe\Framework\Database;

abstract class AbstractEntity
{
    protected $database;
    
    public function __construct(Database $database)
    {
        $this->database = $database;
    }
}
