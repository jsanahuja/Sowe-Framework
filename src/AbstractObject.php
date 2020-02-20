<?php

namespace Sowe\Framework;

use Sowe\Framework\Database;
use Sowe\Framework\AbstractEntity;

abstract class AbstractObject extends AbstractEntity
{
    protected $database;

    protected $identifier;
    protected $data;
    protected $toSave;
    
    public function __construct(Database $database){
        parent::__construct($database);
        $this->toSave = [];
    }

    public function new()
    {
        $this->identifier = null;
        $this->data = [];
        return $this;
    }

    public function load($id)
    {
        $this->data = $this->get($id);
        if (is_null($this->data)) {
            throw new \Exception("No object found");
        }
        $this->identifier = $id;
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
            if(!$this->identifier){
                throw new \Exception("Unable to insert object");
            }
        } else if(!empty($this->toSave)){
            if(!$this->update($this->identifier, $this->toSave)){
                throw new \Exception("Unable to update object");
            }
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
        if(!isset($this->data[$field]) || $this->data[$field] != $value){
            $this->data[$field] = $value;
            $this->toSave[$field] = $value;
        }
        return $this;
    }

    public function getId(){
        return $this->identifier;
    }
}
