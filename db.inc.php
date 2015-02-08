<?php

class Database {
    
    private $db;
    
    public function __construct($config) {
        
        $this->config = $config;

        $set_names = sprintf("SET NAMES '%s';", str_replace('-', '', $this->config['encoding']));

        try {

            $this->db = new PDO('mysql:host=' . $this->config['db_host'] .
                                ';dbname=' . $this->config['db_db'],
                                $this->config['db_user'], $this->config['db_pass'],
                                array(PDO::MYSQL_ATTR_INIT_COMMAND => $set_names));

        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
            die();
        }

    }

    public function query() {
        return new Query($this->db);
    }

}

class Query {

    public $db = null;
    public $sql = '';

    private $table;
    private $join = '';
    private $where_params = [];
    private $params = [];
    private $select;
    private $where = '';
    private $limit = '';
    private $order = '';

    public function __construct($db) {
        $this->db = $db;
        return $this;
    }

    public function select($table, $params) {
        $this->action = 'SELECT ';
        $this->table = $table;
        $field_list = [];
        foreach($params as $table => $fields) {
            foreach($fields as $alias => $field)
            {
                if(strrpos($field, ':') !== false)
                    $sql = str_replace(':', '', $field);
                else
                    $sql = "$table.$field";

                if(is_string($alias)) $sql.= " AS $alias";
                $field_list[] = $sql;
            }
        }
        $this->action .= implode(', ', $field_list) . ' ';
        $this->action .= "FROM {$this->table} ";
        return $this;
    }

    public function update($table, $params, $values) {
        $this->action = "UPDATE {$table} SET ";
        $this->table = $table;
        $field_list = [];
        foreach($params as $table => $fields) {
            foreach($fields as $field)
            {
                if(!array_key_exists($field, $values)) continue;

                if(strrpos($field, ':') !== false)
                    $sql = str_replace(':', '', $field);
                else
                    $sql = "$table.$field";

                $sql .= " = :{$field}";

                $field_list[] = $sql;
                $this->params[$field] = $values[$field];

            }
        }
        $this->action .= implode(', ', $field_list) . ' ';
        return $this;
    }

    public function from($table) {
        $this->from = "FROM $table ";
        $this->table = $table;
        return $this;
    }

    public function inner_join($table_a, $field_a, $table_b = null, $field_b = null) {
        if($table_b == null) $table_b = $this->table;
        if($field_b == null) $field_b = $field_a;

        $this->join .= "INNER JOIN $table_a ON ";
        $this->join .= "$table_a.$field_a = $table_b.$field_b ";

        return $this;
    }

    public function where($name, $cmp, $value) {

        $this->where_params[$name] = [
            'compare' => $cmp,
            'value' => $value
        ];

    }

    public function where_params($params)
    {
        $this->where_params = array_merge($this->where_params, $params);

        if(empty($this->where)) $this->where =  'WHERE 1 ';

        foreach($params as $name => $value) {

            $matches = null;
            preg_match("/^(?<cmp>[~!<>][=]*)*(?<value>.*)$/", $value, $matches);

            $compare = '=';

            switch($matches['cmp']) {

            case '~':  $compare = 'NOT LIKE'; break;
            case '~=': $compare = 'LIKE'; break;
            case '!':  $compare = '<>'; break;
            case '<':  $compare = '<'; break;
            case '>':  $compare = '>'; break;

            };

            $this->where($name, $compare, $matches['value']);

        }

        return $this;
    }

    public function limit($start = 0, $end = 0) {
        $this->limit = [$start, $end];
        return $this;
    }

    public function order_by($field, $mode = 'ASC') {
        $this->order[] = [
            'field' => $field,
            'mode' => $mode
        ];
        return $this;
    }

    public function build() {
        $this->sql = trim($this->action . $this->join . $this->where . $this->order . $this->limit) . ';';
    }

    public function execute() {
        $this->build();
        $query = $this->db->prepare($this->sql);
        $query->execute(array_merge($this->where_params, $this->params));
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

};

?>
