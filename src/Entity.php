<?php

namespace Sowe\Framework;

use Sowe\Framework\AbstractEntity;

class Entity extends AbstractEntity
{
    public function get($id, $fields = ["*"])
    {
        return $this->database
            ->select($this->table)
            ->fields(...$fields)
            ->condition($this->key, "=", $id)
            ->run()
            ->fetchOne();
    }
    
    public function list($fields = ["*"], $filters = null)
    {
        $qb = $this->database
            ->select($this->table)
            ->fields(...$fields);

        if ($filters !== null) {
            $qb->conditions($filters);
        }
        
        return $qb->order($this->key, "DESC")
            ->run()
            ->formattedFetchAll($this->table, $this->key);
    }

    public function update($id, $data)
    {
        $qb = $this->database
            ->update($this->table);
        
        foreach ($data as $key => $value) {
            $qb->set($key, $value);
        }
        
        return $qb->condition($this->key, "=", $id)
            ->run()->num_rows();
    }

    public function create($data)
    {
        $qb = $this->database
            ->insert($this->table);
        
        foreach ($data as $key => $value) {
            $qb->set($key, $value);
        }
        
        $result = $qb->run();
        return $result->id() ? $result->id() : $result->num_rows();
    }

    public function delete($id)
    {
        return $this->database
            ->delete($this->table)
            ->condition($this->key, "=", $id)
            ->run()->num_rows();
    }
}
