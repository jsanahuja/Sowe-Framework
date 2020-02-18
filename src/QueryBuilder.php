<?php

namespace Sowe\Framework;

use Sowe\Framework\Database;
use Sowe\Framework\Query;

class QueryBuilder
{
    protected static $QUERY_TYPES = [
        "SELECT",
        "UPDATE",
        "INSERT",
        "DELETE",
        "QUERY"
    ];

    protected static $ORDER_TYPES = ["ASC", "DESC"];

    protected static $OPERATORS = [
        "=", ">", "<", ">=", "<=", "<>", "!=",
        "IN", "NOT IN", "LIKE", "IS", "IS NOT"
    ];

    private $database;
    private $query;

    private $tables;
    private $fields;
    private $set;
    private $conditions;
    private $joins;
    private $order;
    private $group;
    private $limit;
    private $force;

    public function __construct(string $type, Database $database)
    {
        $type = strtoupper($type);
        if (!in_array($type, static::$QUERY_TYPES)) {
            throw new \Exception("Unrecognized '". $type ."' query type");
        }

        $this->database = $database;
        $this->query = $type;

        $this->tables       = [];
        $this->fields       = [];
        $this->set          = [];
        $this->conditions   = [[]];
        $this->joins        = [];
        $this->order        = [];
        $this->group        = [];
        $this->limit        = false;
        $this->force        = false;
    }

    /**
     * SECURITY PARSERS
     */
    private function escape_table(string $table, string $alias = null)
    {
        return "`" . $table . "`" . ($alias !== null? " `". $alias ."`" : "");
    }

    private function escape_field(string $field)
    {
        return implode(".", array_map(
            function ($item) {
                if ($item == "*") {
                    return $item;
                }
                if (strpos($item, "`")) {
                    throw new \Exception("Refused to parse field '". $item ."' for security reasons");
                }
                return "`" . $item . "`";
            },
            explode(".", $field)
        ));
    }

    private function escape_value($value)
    {
        return "'" . $this->database->real_escape_string($value) . "'";
    }

    /**
     * BUILDER METHODS
     */
    public function table(string $table, string $alias = null)
    {
        switch ($this->query) {
            case "UPDATE":
            case "INSERT":
            case "DELETE":
                if (sizeof($this->tables)) {
                    throw new \Exception("Multiple table selection is not supported for this query type");
                }
                    // no break
            case "SELECT":
                $this->tables[] = $this->escape_table($table, $alias);
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function fields(...$fields)
    {
        switch ($this->query) {
            case "SELECT":
                foreach ($fields as $field) {
                    if(preg_match("/([a-zA-Z\-\_]+)\(([^)]+)\)/", $field, $matches)){
                        $this->fields[] = $matches[1] . "(" . $this->escape_field($matches[2]) . ")";
                    }else{
                        $this->fields[] = $this->escape_field($field);
                    }
                }
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function set($field, $value)
    {
        switch ($this->query) {
            case "UPDATE":
            case "INSERT":
                switch (gettype($value)) {
                    case "boolean":
                        $value = $value ? 1 : 0;
                        $this->set[] = $this->escape_field($field) . " = b" . $this->escape_value($value);
                        break;
                    case "string":
                    case "integer":
                    case "double":
                        $this->set[] = $this->escape_field($field) . " = " . $this->escape_value($value);
                        break;
                    default:
                        throw new \Exception("Unsupported type of value");
                }
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function condition($field, $operator, $value)
    {
        if (!in_array($operator, self::$OPERATORS)) {
            throw new \Exception("Unrecognized operator");
        }

        switch ($this->query) {
            case "SELECT":
            case "UPDATE":
            case "DELETE":
                $index = sizeof($this->conditions) - 1;
                switch (gettype($value)) {
                    case "boolean":
                        $value = $value ? 1 : 0;
                        $this->conditions[$index][] = $this->escape_field($field) . " " . $operator . " b" . $this->escape_value($value);
                        break;
                    case "array":
                        $value = "(". implode(", ", array_map(
                            array($this, "escape_value"),
                            $value
                        )) . ")";
                        $this->conditions[$index][] = $this->escape_field($field) . " " . $operator . " " . $value;
                        break;
                    case "string":
                        if ($value === "NULL") {
                            $this->conditions[$index][] = $this->escape_field($field) . " " . $operator . " NULL";
                            break;
                        }
                        // no break
                    default:
                        $this->conditions[$index][] = $this->escape_field($field) . " " . $operator . " " . $this->escape_value($value);
                }
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function or()
    {
        switch ($this->query) {
            case "SELECT":
            case "UPDATE":
            case "DELETE":
                $this->conditions[] = [];
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function conditions($conditions)
    {
        switch ($this->query) {
            case "SELECT":
            case "UPDATE":
            case "DELETE":
                if (!is_array($conditions)) {
                    throw new \Exception("Wrong condition format: [". print_r($condition, true) . "]");
                }

                // Single condition format [cond, op, val]
                if (is_string($conditions[0])) {
                    return $this->condition(...$conditions);
                }

                // Format check. Conditions? Groups of conditions? Mixed?
                $groupsFound = false;
                $conditionsFound = false;
                foreach ($conditions as $element) {
                    // Here we must have at least an array of arrays
                    if (!is_array($element)) {
                        throw new \Exception("Wrong condition format: [". print_r($element, true) . "]");
                    }

                    // Condition or a group of conditions?
                    if (is_string($element[0])) {
                        $conditionsFound = true;
                    } elseif (is_array($element[0])) {
                        $groupsFound = true;
                    } else {
                        throw new \Exception("Wrong condition format: [". print_r($condition, true) . "]");
                    }
                }

                // Format parsing.
                if ($conditionsFound && $groupsFound) {
                    // Format mixed to [[[cond, op, val]]]
                    foreach ($conditions as &$element) {
                        if (is_string($element[0])) {
                            $element = [$element];
                        }
                    }
                } elseif ($conditionsFound) {
                    // Format [[cond, op, val]] to [[[cond, op, val]]]
                    $conditions = [$conditions];
                }

                /**
                 * Format [[[cond, op, val]]] where:
                 * [
                 *   [ <- OR group of clauses
                 *      [condition, operator, value], <- AND clauses
                 *      [condition, operator, value]
                 *   ],
                 *   [ <- OR group of clauses
                 *      [condition, operator, value]
                 *   ]
                 * ]
                 *
                 * So:
                 * [
                 *   [
                 *     ["name", "=", "John Doe"],
                 *     ["age",  "=", 25]
                 *   ],
                 *   [
                 *     ["name", "=", "John Doe"],
                 *     ["age",  "=", 40]
                 *   ]
                 * ]
                 * Would be
                 * WHERE (
                 *   (name = 'John Doe' AND age = 25) OR
                 *   (name = 'John Doe' AND age = 40) OR
                 * )
                 */
                foreach ($conditions as $conditionsGroup) {
                    foreach ($conditionsGroup as $condition) {
                        if (sizeof($condition) != 3) {
                            throw new \Exception("Wrong condition format: [". print_r($condition, true) . "]");
                        }
                        $this->condition(...$condition);
                    }
                    $this->or();
                }
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function limit($offset, $limit = null)
    {
        switch ($this->query) {
            case "SELECT":
            case "UPDATE":
            case "DELETE":
                if ($limit === null) {
                    $this->limit = "LIMIT ". intval($offset);
                } else {
                    $this->limit = "LIMIT ". intval($offset) . "," . intval($limit);
                }
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function innerJoin($table, $alias, $field1, $operator, $field2)
    {
        return $this->join("INNER", $table, $alias, $field1, $operator, $field2);
    }
    
    public function leftJoin($table, $alias, $field1, $operator, $field2)
    {
        return $this->join("LEFT", $table, $alias, $field1, $operator, $field2);
    }

    public function rightJoin($table, $alias, $field1, $operator, $field2)
    {
        return $this->join("RIGHT", $table, $alias, $field1, $operator, $field2);
    }

    public function join($type, $table, $alias, $field1, $operator, $field2)
    {
        switch ($this->query) {
            case "SELECT":
                $this->joins[] = strtoupper($type) . " JOIN " . $this->escape_table($table, $alias).
                " ON ". $this->escape_field($field1) ." ". $operator ." ". $this->escape_field($field2);
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function order($field, $order)
    {
        if (!in_array($order, self::$ORDER_TYPES)) {
            throw new \Exception("Unrecognized order method");
        }

        switch ($this->query) {
            case "SELECT":
                $this->order[] = $this->escape_field($field) . " " . $order;
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function group(...$fields)
    {
        switch ($this->query) {
            case "SELECT":
                foreach ($fields as $field) {
                    $this->group[] = $this->escape_field($field);
                }
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function force()
    {
        switch ($this->query) {
            case "DELETE":
            case "UPDATE":
                $this->force = true;
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $this;
    }

    public function buildConditions()
    {
        $this->conditions = array_filter($this->conditions, "sizeof");
        switch ($this->query) {
            case "DELETE":
            case "UPDATE":
                if (sizeof($this->conditions) == 0) {
                    if ($this->force) {
                        return "";
                    }
                    throw new \Exception("Unnable to perform an UPDATE/DELETE without conditions. Call ->force() to force it");
                }
                // no break
            case "SELECT":
                if (sizeof($this->conditions) == 0) {
                    return "";
                }
                return "WHERE ". implode(" OR ", array_map(function ($group) {
                    return implode(" AND ", $group);
                }, $this->conditions));
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
    }

    public function toSQL()
    {
        switch ($this->query) {
            case "SELECT":
                if (sizeof($this->fields) == 0) {
                    $this->fields[] = "*";
                }

                $q = implode(" ", array_filter([
                    "SELECT",
                    implode(",", $this->fields),
                    "FROM",
                    implode(",", $this->tables),
                    implode(" ", $this->joins),
                    $this->buildConditions(),
                    sizeof($this->order) ? "ORDER BY ". implode(", ", $this->order) : "",
                    sizeof($this->group) ? "GROUP BY ". implode(", ", $this->group) : "",
                    $this->limit !== false ? $this->limit : ""
                ]));
                break;
            case "UPDATE":
                if (sizeof($this->set) == 0) {
                    throw new \Exception("Cannot build an update without changes");
                }
                $q = implode(" ", array_filter([
                    "UPDATE",
                    implode("", $this->tables),
                    "SET",
                    implode(", ", $this->set),
                    $this->buildConditions(),
                    $this->limit !== false ? $this->limit : ""
                ]));
                break;
            case "INSERT":
                if (sizeof($this->set) == 0) {
                    throw new \Exception("Cannot build an insert without values");
                }
                $q = implode(" ", array_filter([
                    "INSERT INTO",
                    implode("", $this->tables),
                    "SET",
                    implode(", ", $this->set)
                ]));
                break;
            case "DELETE":
                $q = implode(" ", array_filter([
                    "DELETE FROM",
                    implode(",", $this->tables),
                    $this->buildConditions(),
                    $this->limit !== false ? $this->limit : ""
                ]));
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return $q;
    }

    public function build()
    {
        switch ($this->query) {
            case "SELECT":
                if (sizeof($this->fields) == 0) {
                    throw new \Exception("Cannot build a select query without fields");
                }

                $q = implode(" ", [
                    "SELECT",
                    implode(",", $this->fields),
                    "FROM",
                    implode(",", $this->tables),
                    implode(" ", $this->joins),
                    $this->buildConditions(),
                    sizeof($this->group) ? "GROUP BY ". implode(", ", $this->group) : "",
                    sizeof($this->order) ? "ORDER BY ". implode(", ", $this->order) : "",
                    $this->limit !== false ? $this->limit : ""
                ]);
                break;
            case "UPDATE":
                if (sizeof($this->set) == 0) {
                    throw new \Exception("Cannot build an update without changes");
                }
                $q = implode(" ", [
                    "UPDATE",
                    implode("", $this->tables),
                    "SET",
                    implode(", ", $this->set),
                    $this->buildConditions()
                ]);
                break;
            case "INSERT":
                if (sizeof($this->set) == 0) {
                    throw new \Exception("Cannot build an insert without values");
                }
                $q = implode(" ", [
                    "INSERT INTO",
                    implode("", $this->tables),
                    "SET",
                    implode(", ", $this->set)
                ]);
                break;
            case "DELETE":
                $q = implode(" ", [
                    "DELETE FROM",
                    implode(",", $this->tables),
                    $this->buildConditions(),
                    $this->limit !== false ? $this->limit : ""
                ]);
                break;
            default:
                throw new \Exception("This method is not supported for this query type");
                break;
        }
        return new Query($this->database, $this->toSQL());
    }

    public function run()
    {
        $query = $this->build()->run();
        return $query;
    }
}
