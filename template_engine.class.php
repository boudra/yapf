<?php

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
	echo ob_get_clean();
	ob_start();
	eval("?> " . $this->compile($view));
	echo ob_get_clean();
    ob_start();
    }

    public function _block() {
    ob_clean();
    echo ob_get_contents();
    }

    public function end_block() {
    }

    public function _include() {
    }

    public function compile($view) {
	$view = $this->find_view($dirs, $view);
	if($view === null) return '';
	$result = file_get_contents($view);
	$result = preg_replace('/\{{2}\ *(\$\w+)\ *\}{2}/', '<?php echo $1; ?>', $result);
	$result = preg_replace('/\{%\ *(block|extend|include)\ *(\w+)\ *%\}/', '<?php $this->_$1(\'$2\'); ?>', $result);
	$result = preg_replace('/\{%\ *(endblock|endextend|endinclude)\ *%\}/', '<?php $this->end_$1(); ?>', $result);
	$result = preg_replace('/\{%\ *(foreach|else if|for|if|while)\ *(.*)%\}/', '<?php $1($2): ?>', $result);
	$result = preg_replace('/\{%\ *(else)\ *%\}/', '<?php $1: ?>', $result);
	$result = preg_replace('/\{%\ *end(\w+)\ *%\}/', '<?php end$1; ?>', $result);
	$result = preg_replace('/\{\ *(?!\/)(.*)\ *\}/', '<?php $1; ?>', $result);
    echo '<pre>' . htmlspecialchars($result) . '</pre>';
	return $result;
    }

    public function render() {
	extract($this->data);
	ob_start();
	eval("?> " . $this->compiled);
	return ob_get_clean();
    }

    private function find_view($dirs, $name) {
	foreach($this->dirs as $dir) {
	    if(is_dir($dir) && file_exists("{$dir}/{$name}.php")) {
		return "{$dir}/{$name}.php";
	    }
	}
	return null;
    }

};

?>
