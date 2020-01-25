<?php

namespace Sowe\Framework;

use Sowe\Framework\Database;

abstract class AbstractEntity
{
    protected $database;
    
    public function __construct(Database $database)
    {
        if (!isset(static::$table) || !isset(static::$key)) {
            throw new \Exception("Entity should define static table and key variables");
        }
        $this->database = $database;
    }

    public function get($id, $fields = ["*"])
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        return $this->database
            ->select(static::$table)
            ->fields(...$fields)
            ->condition(static::$key, "=", $id)
            ->run()
            ->fetchOne();
    }
    
    public function list($fields = ["*"], $filters = null)
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $qb = $this->database
            ->select(static::$table)
            ->fields(...$fields);

        if ($filters !== null) {
            $qb->conditions($filters);
        }
        
        return $qb->order(static::$key, "DESC")
            ->run()
            ->formattedFetchAll(static::$table, static::$key);
    }

    public function update($id, $data)
    {
        $qb = $this->database
            ->update(static::$table);
        
        foreach ($data as $key => $value) {
            $qb->set($key, $value);
        }
        
        return $qb->condition(static::$key, "=", $id)
            ->run()->num_rows();
    }

    public function create($data)
    {
        $qb = $this->database
            ->insert(static::$table);
        
        foreach ($data as $key => $value) {
            $qb->set($key, $value);
        }
        
        $result = $qb->run();
        return $result->id() ? $result->id() : $result->num_rows();
    }

    public function delete($id)
    {
        return $this->database
            ->delete(static::$table)
            ->condition(static::$key, "=", $id)
            ->run()->num_rows();
    }
}
