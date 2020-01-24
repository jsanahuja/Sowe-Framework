<?php

namespace Sowe\Framework;

use Sowe\Framework\QueryBuilder;

class Database extends \mysqli
{
    public function __construct($host, $user, $password, $database, $charset = "utf8")
    {
        @parent::__construct($host, $user, $password, $database);

        if ($this->connect_errno) {
            throw new \Exception("Database connection error: ".  $this->connect_error);
        }

        if (!$this->set_charset($charset)) {
            throw new \Exception("Unnable to set database charset: ".  $this->error);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function select(string $table, string $alias = null)
    {
        $qb = new QueryBuilder("select", $this);
        return $qb->table($table, $alias);
    }

    public function update(string $table, string $alias = null)
    {
        $qb = new QueryBuilder("update", $this);
        return $qb->table($table, $alias);
    }

    public function insert(string $table, string $alias = null)
    {
        $qb = new QueryBuilder("insert", $this);
        return $qb->table($table, $alias);
    }

    public function delete(string $table, string $alias = null)
    {
        $qb = new QueryBuilder("delete", $this);
        return $qb->table($table, $alias);
    }
}
