<?php

namespace App\Database;

use App\Core\Config;

class Database {

    private $db;

    public function __construct(Config $config) {

        $this->config = $config;

        $set_names = sprintf("SET NAMES '%s';", str_replace('-', '', $config->encoding));

        $this->db = new \PDO('mysql:host=' . $config->db_host .
                    ';dbname=' . $config->db_db,
                    $config->db_user, $config->db_pwd,
                    array(\PDO::MYSQL_ATTR_INIT_COMMAND => $set_names));


    }

    public function last_id() {
        return $this->db->lastInsertId();
    }

    public function table($table, $alias = null) {
        return (new Query($this->db))->table($table, $alias);
    }

    public function select($table, $alias = null) {
        return (new Query($this->db))->select($table, $alias);
    }

    public function insert($table, $alias = null) {
        return (new Query($this->db))->insert($table, $alias);
    }

    public function delete($table, $alias = null) {
        return (new Query($this->db))->select($table, $alias);
    }

}


?>
