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
    
    public function new()
    {
        $this->identifier = null;
        $this->data = [];
        $this->toSave = [];
        return $this;
    }

    public function lazyload($id){
        $this->identifier = $id;
        $this->data = [];
        $this->toSave = [];
        return $this;
    }

    public function load($id)
    {
        $this->data = $this->get($id);
        $this->toSave = [];
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
            $this->update($this->identifier, $this->toSave);
        }
        return $this;
    }

    public function remove()
    {
        if (is_null($this->identifier)) {
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
        if(!isset($this->data[$field]) || $this->data[$field] != $value){
            $this->data[$field] = $value;
            $this->toSave[$field] = $value;
        }
        return $this;
    }

    public function getId(){
        if(isset($this->data[static::$key])){
            return $this->data[static::$key];
        }
        return $this->identifier;
    }

    public function setId($id){
        $this->setData(static::$key, $id);
        return $this;
    }
}
