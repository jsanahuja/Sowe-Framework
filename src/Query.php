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
            throw new \Exception("SQL Error: ". $this->database->errno .":". $this->database->error);
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

    public function formattedFetchAll($main_table, $key)
    {
        $groups = [];
        $fields = [];
        $f = $this->result->fetch_fields();
        foreach ($f as $field) {
            if ($field->orgtable != $main_table) {
                $fields[$field->orgname] = $field->orgtable;
                $groups[$field->orgtable] = true;
            } else {
                $fields[$field->orgname] = false;
            }
        }
        $groups = array_keys($groups);
        
        $data = [];
        foreach ($this->fetchAll() as $entity) {
            $obj = [];
            foreach ($entity as $field => $value) {
                if ($fields[$field] === false) {
                    $obj[$field] = $value;
                } else {
                    if (!isset($obj[$fields[$field]])) {
                        $obj[$fields[$field]] = [[]];
                    }
                    $obj[$fields[$field]][0][$field] = $value;
                }
            }
            if (!isset($data[$obj[$key]])) {
                foreach ($groups as $g) {
                    if (!array_filter(array_values($obj[$g][0]))) {
                        unset($obj[$g][0]);
                    }
                }
                $data[$obj[$key]] = $obj;
            } else {
                foreach ($groups as $g) {
                    $data[$obj[$key]][$g][] = $obj[$g][0];
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
