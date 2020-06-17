<?php

namespace Sowe\Framework;

use Sowe\Framework\Database;

class Query
{
    private $database;
    private $query;
    private $result;

    public function __construct(Database $database, string $query, array $params = [])
    {
        $this->database = $database;
        $this->query = $query;
        $this->parse_params($params);
    }

    private function parse_params($params)
    {
        if (sizeof($params)) {
            foreach ($params as $key => $value) {
                $this->query = str_replace($key, "'". $this->database->real_escape_string($value) ."'", $this->query);
            }
        }
    }

    public function run()
    {
        $q = $this->database->query($this->query);
        if (!$q) {
            throw new \Exception("SQL Error: ". $this->database->errno .":". $this->database->error . " (Query: ". $this->query .")");
        }
        $this->result = $q;
        return $this;
    }

    public function num_rows()
    {
        return $this->database->affected_rows;
    }

    public function id()
    {
        return $this->database->insert_id;
    }

    public function fetchOne()
    {
        return $this->result->fetch_assoc();
    }

    public function fetchAll()
    {
        return $this->result->fetch_all(MYSQLI_ASSOC);
    }

    public function formattedFetchAll()
    {
        $fields = $this->result->fetch_fields();
        $entities = [];
        foreach ($this->result->fetch_all(MYSQLI_NUM) as $e) {
            $entity = [];
            // Iterating over values
            foreach($e as $key => $value){
                $field = $fields[$key];
                // If this field name is taken lets not override it.
                if(isset($entity[$field->name])){
                    if($field->table != ""){
                        // Trying to set table_alias.field_name
                        $entity[$field->table . "." . $field->name] = $value;
                    }else{
                        // Setting table_name.field_name
                        $entity[$field->orgtable . "." . $field->name] = $value;
                    }
                }else{
                    // Field is not set, lets use its name
                    $entity[$field->name] = $value;
                }
            }
            // Using first field as key
            $entities[$e[0]] = $entity;
        }
        return $entities;
    }

    public function formattedJoinFetchAll($main_table, $joinkey, $primarykey){
        // Getting fields and mapping each to tables
        $f = $this->result->fetch_fields();
        $main_fields = [];
        $join_fields = [];
        foreach($f as $field){
            if($field->orgtable == $main_table){
                $main_fields[] = $field->name;
            }else{
                $join_fields[] = $field->name;
            }
        }

        // Setting values
        $data = [];
        foreach ($this->fetchAll() as $entity) {
            $pk = $entity[$primarykey];
            $jk = $entity[$joinkey];

            if(!isset($data[$pk])){
                $data[$pk] = [];
            }
            $data[$pk][$jk] = [];

            foreach($entity as $field => $value){
                if(in_array($field, $main_fields)){
                    $data[$pk][$field] = $value;
                }else if($field != $joinkey){
                    $data[$pk][$jk][$field] = $value;
                }
            }
        }
        
        return $data;
    }

    public function formattedFetchOne($main_table, $key)
    {
        $result = $this->formattedFetchAll($main_table, $key);
        return end($result);
    }

    public function __toString()
    {
        return $this->query;
    }
}
