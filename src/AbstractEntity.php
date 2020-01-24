<?php

namespace Sowe\Framework;

use Sowe\Framework\Database;

abstract class AbstractEntity
{
    protected $database;
    protected $table;
    protected $key;

    public function __construct(Database $database, string $table, string $key)
    {
        $this->database = $database;
        $this->table = $table;
        $this->key = $key;
    }
}
