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

    public function query($table = null, $alias = null) {
        return ($table === null) ? new Query($this->db) : new Query($table, $alias);
    }

    public function table($table, $alias = null) {
        return (new Query($this->db))->table($table, $alias);
    }

    public function select($table, $alias = null) {
        return (new Query($this->db))->select($table, $alias);
    }

    public function delete($table, $alias = null) {
        return (new Query($this->db))->select($table, $alias);
    }

}

class Join {
    private $query = null;
    public function __construct(Query $query) {
        $this->query($query);
    }
    public function on() {
        return $this->query;
    }
}

class Query {

    public $db = null;
    public $sql = '';

    private $where_params = [];
    private $tables = [];
    private $last_table = null;

    private $fields = [];

    private $action = 'SELECT';

    private $joins = [];
    private $last_join = null;

    private $values = [];
    private $prepare_values = [];
    private $limit = [];
    private $order = [];

    public function __construct(PDO $db) {
        $this->db = $db;
        return $this;
    }

    public function table($name, $alias = null) {
        if($alias === null) $alias = $name;
        $this->tables[$alias] = $name;
        $this->last_table = $alias;
        return $this;
    }

    public function select($name, $alias = null) {
        $this->action = 'SELECT';
        return $this->table($name, $alias);
    }

    public function delete($name, $alias = null) {
        $this->action = 'DELETE';
        return $this->table($name, $alias);
    }

    /* TODO */
    public function tables() {
        return $this;
    }

    public function fields() {

        $fields = func_get_args();

        foreach ($fields as $field) {
            $this->field($field);
        }

        return $this;
    }

    public function field($field) {
        $this->fields[] = $field;
        return $this;
    }

    public function from($table) {
        $this->from = "FROM $table ";
        $this->table = $table;
        return $this;
    }

    public function inner_join($name, $alias = null) {
        $this->add_join('INNER JOIN', $name, $alias);
        return $this;
    }

    private function add_join($type, $name, $alias = null) {

        if($alias === null) $alias = $name;

        $this->joins[$alias] = [
            'right' => $name,
            'left' => $this->last_table,
            'type' => $type,
            'rules' => []
        ];

        $this->last_join = $alias;
        
    }

    private function add_join_rule($args, $type = null) {
        
        $nargs = count($args);
        $join = &$this->joins[$this->last_join];

        if($nargs == 1) {

            $rule = [
                'a' => $args[0],
                'b' => $args[0],
                'cmp' => '='
            ];

        } else if($nargs == 2) {

            $rule = [
                'a' => $args[0],
                'b' => $args[1],
                'cmp' => '='
            ];

        } else if($nargs == 3) {

            $rule = [
                'a' => $args[0],
                'b' => $args[3],
                'cmp' => $args[2]
            ];

        }

        $rule['concat'] = $type;
        $join['rules'][] = $rule;

    }

    public function on() {
        $this->add_join_rule(func_get_args());
        return $this;
    }

    public function maybe() {
        $this->add_join_rule(func_get_args(), 'OR');
        return $this;
    }

    public function where($name, $cmp, $value) {

        $this->where_params[$name] = [
            'compare' => $cmp,
            'value' => $value
        ];

        return $this;

    }

    public function where_params($params)
    {

        foreach($params as $name => $value) {

            $matches = null;
            preg_match("/^(?<cmp>[~!<>=]{1,2})*(?<value>.*)$/", $value, $matches);

            $compare = '=';

            switch($matches['cmp']) {

            case '!~': $compare = 'NOT LIKE'; break;
            case '~':  $compare = 'LIKE'; break;
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

    private function build_where() {
        $where_rules = [];

        foreach($this->where_params as $name => $where) {
            $is_value = ($where['value'][0] != "`");
            $key = str_replace('.', '_', $name);
            $where_rules[] = "{$name} {$where['compare']} " . ($is_value ? ":{$key}" : trim($where['value'], "`"));
            if($is_value) $this->prepare_values[$key] = $where['value'];
        }

        $where_sql = implode("\nAND ", $where_rules);

        return trim($where_sql);
    }

    private function build_from() {
        $from = [];

        foreach ($this->tables as $alias => $table) {
            $name = $table;
            if($table != $alias) $name .= ' AS ' . $alias;
            $from[] = $name;
        }

        $joins = [];

        foreach($this->joins as $alias => $join) {
            $join_rule_sql = $join['type'] . ' ' . $join['right'] . ' AS ' . $alias . "\nON ";
            foreach($join['rules'] as $rule) {
                if($rule['concat'] !== null) $join_rule_sql .= "{$rule['concat']} ";
                $join_rule_sql .= "{$join['left']}.{$rule['a']} {$rule['cmp']} {$alias}.{$rule['b']}\n";
            }
            $joins[] = $join_rule_sql;
        }

        return "\n" . sprintf("FROM %s\n", implode(', ', $from)) . implode('', $joins);
    }

    private function build_order() {

        $fields = [];

        foreach($this->order as $order) {
            $fields[] = "{$order['field']} {$order['mode']}"; 
        }

        return count($fields) > 0 ? sprintf("ORDER BY %s\n", implode(', ', $fields)) : '';

    }

    private function build_limit() {
        return count($this->limit) == 2 ?
                                   "LIMIT {$this->limit[0]} {$this->limit[1]}" :
                                   "";
    }

    public function build() {

        $sql = '';

        switch($this->action) {

        case 'SELECT': {

            $sql = sprintf("SELECT %s ", implode(', ', $this->fields));

            $from_sql  = $this->build_from();
            $where_sql = $this->build_where();

            $sql .= $from_sql;

            if(strlen($where_sql) > 0)
                $sql .= sprintf("WHERE %s\n", $where_sql);

            $sql .= $this->build_order();
            $sql .= $this->build_limit();

            $sql .= ";";

            break;

        }
            
        };

        $this->sql = $sql;

        return $this;
    }

    public function fetch() {
        if(strlen($this->sql) === 0) $this->build();
        // echo "EXEC: {$this->sql}\n";
        // echo "WITH: "; print_r($this->prepare_values); echo "\n";
        $query = $this->db->prepare($this->sql);
        $query->execute($this->prepare_values);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

};

?>
