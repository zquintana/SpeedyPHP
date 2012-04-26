<?php 
use \Speedy\Utility\Inflector;

class Generator extends Speedy\Task {
	
	const CONTROLLERS_DIR	= "controllers";
	
	const HELPERS_DIR		= "helpers";
	
	const MODELS_DIR		= "models";
	
	const VIEWS_DIR			= "views";
	
	const MIGRATION_DIR		= "migrate";
	
	public $alias = "g";
	
	private $_generators = array(
		'test' => 'generateTest',
		'controller' => 'generateController',
		'model'	=> 'generateModel',
		'migration'	=> 'generateMigration'
	);
	
	private $_variables = array();
	
	
	
	/**
	 * Prints help for task
	 */
	public function help() {
		$help = <<<EOF
Generator kit. Use without command for help or with -h option.
usage:
	    speedy generator [command]	
		
Commands:
test		-- Generators test
controller	-- Generate controller
EOF;
		output($help);
	}
	
	/**
	 * Checks if a generator exists 
	 * @param string $name
	 * @return boolean
	 */
	public function hasGenerator($name) {
		return ($this->_generators[$name]) ? true : false;
	}
	
	/**
	 * Get genenator function
	 * @param string $name
	 * @return string of function name
	 */
	public function getGenerator($name) {
		return $this->_generators[$name];
	}
	
	/**
	 * Get all generators
	 * @return array
	 */
	public function generators() {
		return $this->_generators;
	}

	/**
	 * Self explainatory
	 */
	public function defaultTask() {
		$command	= $this->getData(0);
		
		if (!$this->hasGenerator($command)) {
			$this->help();
			return 1;
		}
		
		$callback	= $this->getGenerator($command);
		if (is_array($callback)) {
			// TODO: add array callback functionality
		} else {
			$callback	= array($this, $callback);
			return call_user_func($callback);
		}
		
		return 0;
	}
	
	public function generateTest() {
		$class = $this->getData(1);
		output("Generating test harness for: $class");
		
		$content= $this->_testTemplate(array('className' => $class));
		// TODO: fix later to user current project directory
		$file	= SPEEDY_CLI . DS . 'tests' . DS . $class . ".php";
		if (file_exists($file)) {
			output("File already exists for {$class}.php");
			return 1;
		}
		
		$fh	= fopen($file, 'w');

		fwrite($fh, $content);
		fclose($fh);
		output("Saved the file");
	}
	
	public function generateModel() {
		if (!APP_LOADED) {
			output('Must be in the application directory');
			return 1;
		}
		
		$app		= App::instance();
		$name		= $this->getData(1);
		$nameArray	= $this->_cleanName($name);
		$name		= array_pop($nameArray);
		
		if (count($nameArray)) {
			$this->_recurseMkdir($nameArray, array(
				self::MODELS_DIR,
			));
		}
		
		$this->set('namespace', $app->name());
		$this->set('model',		$name);
		$template	= $this->getTemplate('models' . DS . 'ActiveRecord.php');
		
		output("Create {$name}.php");
		$name	= (count($nameArray)) ? implode(DS, $nameArray) . DS . $name : $name;
		$path	= APP_PATH . DS . self::MODELS_DIR . DS . $name . '.php';
		file_put_contents($path, $template);
	}
	
	/**
	 * Task generate the controller
	 */
	public function generateController() {
		if (!APP_LOADED) {
			output('Must be in the application directory');
			return 1;
		}
		
		$app		= App::instance();
		$name		= $this->getData(1);
		$nameArray	= $this->_cleanName($name);
		$name		= array_pop($nameArray);
		
		if (count($nameArray)) {
			$this->_recurseMkdir($nameArray, array(
				self::CONTROLLERS_DIR,
				self::HELPERS_DIR
			));
		}
		
		//$views		= (count($nameArray)) ? self::VIEWS_DIR . DS .implode(DS, $nameArray) : self::VIEWS_DIR;
		$viewPath	= $nameArray;
		$viewPath[]	= strtolower($name);
		
		$this->_recurseMkdir($viewPath, self::VIEWS_DIR);
	
		$this->set('namespace', 	$app->name());
		$this->set('controller',	$name);
		$this->set('actions',		'');
		$template	= $this->getTemplate('BaseController.php');
		
		output("Create {$name}.php");
		$path	= APP_PATH . DS . self::CONTROLLERS_DIR . DS . implode(DS, $nameArray) . DS . $name . '.php';
		file_put_contents($path, $template);
	}
	
	/**
	 * Task to generate a migration
	 */
	public function generateMigration() {
		if (!APP_LOADED) {
			output('Must be in the application directory');
			return 1;
		}
		
		$name	= Inflector::underscore($this->getData(1));
		$count	= count($this->getData());
		if (preg_match("/^create_table_([\w]+)/", $name, $matches)) {
			$table	= $matches[1];
			$actions	= '$this->create_table("' . $table . '", function() {' . "\n";
			
			for ($i = 2; $i < $count; $i++) {
				$columnDef	= $this->getData($i);
				$def	= explode(':', $columnDef);
				$actions	.= "\t\t\t" . '$this->' . $def[1] . '("' . $def[0] .'");' . "\n";
			}
			
			$actions 	.= "\n\t\t\t" . '$this->timestamps();' . "\n";
			$actions	.= "\t\t});";
		}
		
		$this->set('name', Inflector::camelize($name));
		$this->set('actions', $actions);
		$file	= @date('YmdHis') . '_' . $name . '.php';
		$content= $this->getTemplate('Migration.php');
		
		output("Create {$file}");
		$path	= APP_ROOT . DS . 'db' . DS . self::MIGRATION_DIR . DS . $file;
		file_put_contents($path, $content);
	}
	
	/**
	 * recursively build path
	 * @param array $pathArr
	 * @return boolean 
	 */
	private function _recurseMkdir(array $pathArr, $subDir) {
		// if $subDir is an array then loop them agaisn't this function
		if (is_array($subDir)) {
			foreach ($subDir as $dir) {
				$success = $this->_recurseMkdir($pathArr, $dir);
				
				// if mkdir fails, fail the function
				if (!$success) return false;
			} 
			
			return true;
		} elseif (!is_string($subDir)) {
			// unexpected parameter type
			return false;
		}
		
		// path to subDir(controllers, views, helpers...)
		$rootPath	= APP_PATH . DS;
		$current	= '';
		
		// Loop the parts, check if already exists, then make
		foreach ($pathArr as $part) {
			$current .= DS . strtolower($part);
			
			// if the directory already exists, then skip the loop and continue
			if (is_dir($rootPath . $subDir . $current)) {
				output("Existing {$subDir}{$current}");
				continue;
			}
			
			output("Create {$subDir}{$current}");
			// if the make dir fails then bail if fail return
			if (!mkdir($rootPath . $subDir . $current)) return false;
		}
		
		return true;
	}
	
	/**
	 * Clean up the name for output
	 * @param string $name
	 * @return array of names to path
	 */
	private function _cleanName($name) {
		if (strpos($name, '/') !== false) {
			$nameArray	= explode('/', $name);
			$lastIndex	= count($nameArray) - 1;
			
			$nameArray[$lastIndex]	= ucfirst(end($nameArray));
		} else $nameArray = array(ucfirst($name));
		
		return $nameArray;
	}
	
	/**
	 * Set template variable
	 * @param string $name
	 * @param mixed $value
	 */
	private function set($name, $value) {
		$this->_variables[$name] = $value;
		return $this;
	}
	
	/**
	 * Get template from file 
	 * @param string $tpl
	 */
	public function getTemplate($tpl) {
		extract($this->variables());
	
		$content	= include SPEEDY_TEMPLATES . $tpl;
		return $content;
	}
	
	/**
	 * Get all variables
	 * @return array 
	 */
	public function variables() {
		return $this->_variables;
	}
	
	/**
	 * Generator for test template
	 * @param array $variables
	 */
	private function _testTemplate(array $variables) {
		if (is_array($variables)) {
			extract($variables);
		}
		
		return <<<TPL
<?php
require_once SPEEDY_PATH . "Loader.php";

\Speedy\import('speedy.test');				// import the test subclass
// \Speedy\import('speedy.object');			// import the class

class $className extends \Speedy\Test {
	
	private \$_instance;
	
	public function setup() {
		output("Setup the test here");
		\$this->_instance = new $className(); 
	}
	
	public function test() {
		\$instance =& \$this->_instance;
		
		output("Do test here");
	}
	
}
TPL;
	}
	
}

?>