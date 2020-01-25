<?php

namespace Sowe\Framework;

use Sowe\Framework\Database;
use Sowe\Framework\AbstractEntity;

abstract class AbstractObject extends AbstractEntity
{
    protected $database;

    protected $identifier;
    protected $data;
    
    public function new()
    {
        $this->identifier = null;
        $this->data = [];
        return $this;
    }

    public function load($id)
    {
        $this->identifier = $id;
        $this->data = $this->get($id);
        unset($this->data[static::$key]);
        return $this;
    }

    public function save()
    {
        if (empty($this->data)) {
            throw new \Exception("There is no data to save");
        }
        if (is_null($this->identifier)) {
            $this->identifier = $this->create($this->data);
        } else {
            $this->update($this->identifier, $this->data);
        }
        return $this;
    }

    public function remove()
    {
        if (!is_null($this->identifier)) {
            throw new \Exception("Triying to delete non-created object");
        }
        $this->delete($this->identifier);
    }

    public function getData($field)
    {
        if ($field === static::$key) {
            if (!is_null($this->identifier)) {
                throw new \Exception("Triying to get uninitialized identifier '". $field ."'");
            } else {
                return $this->identifier;
            }
        }
        if (!isset($this->data[$field])) {
            throw new \Exception("Triying to get uninitialized field '". $field ."'");
        }
        return $this->data[$field];
    }

    public function setData($field, $value)
    {
        if ($field === static::$key) {
            throw new \Exception("Cannot set object identifier");
        }
        $this->data[$field] = $value;
        return $this;
    }
}
