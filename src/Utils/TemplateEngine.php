<?php

namespace App\Utils;

class TemplateEngine {

    private $compiled;
    private $dirs;
    private $data;
    private $blocks = [];

    public function add_dir($dir) {
        $this->dirs[] = $dir;
    }

    public function display($view, $data = null) {
        $this->compiled = $this->compile($view);
        $this->data = $data;
        return $this->render();
    }

    public function _extend($view) {
        extract($this->data);
        ob_start();
        eval("?> " . $this->compile($view));
        echo ob_get_clean();
        ob_start();
    }

    public function compile($view) {
        $view = $this->find_view($dirs, $view);
        if($view === null) return '';
        $result = file_get_contents($view);
        return $result;
    }

    public function render() {
        extract($this->data);
        ob_start();
        eval("?> " . $this->compiled);
        return ob_get_clean();
    }

    private function find_view($dirs, $name) {
        if(empty($this->dirs)) $this->dirs[] = realpath('./views');
        foreach($this->dirs as $dir) {
            if(is_dir($dir) && file_exists("{$dir}/{$name}.php")) {
                return "{$dir}/{$name}.php";
            }
        }
        return null;
    }

};

?>
