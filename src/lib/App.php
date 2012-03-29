<?php 
namespace Vzed;

require_once "Loader.php";
import('vzed.object');
import('vzed.router');
import('vzed.dispatcher');

use \Vzed\Router;

class App extends Object {
	
	protected static $_instance = null;
	
	protected $_request;
	
	private $_router;
	
	
	/**
	 * Set the singleton app
	 * @param \Vzed\App $app
	 * @return \Vzed\App $app
	 */
	protected static function _setInstance(\Vzed\App $app) {
		self::$_instance = $app;
		return $app;
	}
	
	/**
	 * Get the singleton instance
	 * @throws \Exception
	 * @return \Vzed\App
	 */
	public static function instance() {
		if (self::$_instance === null) {
			throw new \Exception('App class already has one instance');
		}
		
		return self::$_instance;
	}
	
	/**
	 * Strap together all resources
	 */
	public function __construct() {
		$this->_setRequest(new Request());
		
		self::_setInstance($this);
	}

	/**
	 * Bootstrap all application
	 * @return $this;
	 */
	public function bootstrap() {
		$methods = $this->getBootstrapMethods();
		foreach ($methods as $method) {
			$this->{$method}();
		}
		
		// TODO: Hook this up to run user defined router draw method
		$router	= Router::getInstance();
		$router->draw();
		
		return $this;
	}
	
	public function run() {
		Dispatcher::run($this->router()->getRoute());
	}
	
	/**
	 * Getter for just bootstrap methods
	 * @return array of bootstrap methods
	 */
	private function getBootstrapMethods() {
		$methods = get_class_methods($this);
		return array_filter($methods, array($this, 'filterMethods'));
	}
	
	/**
	 * Filter methods array for initMethods only 
	 * @param array $array
	 */
	public function filterMethods($array) {
		return preg_match("/^init[A-Z]{1,}[\w]+$/", $value);
	}
	
	/**
	 * Setter for router
	 * @param \Vzed\Router $router
	 */
	private function _setRouter(&$router) {
		$this->_router	=& $router;
		return $this;
	}
	
	/**
	 * Getter for router
	 * @return \Vzed\Router
	 */
	public function router() {
		if (!$this->_router) {
			$this->_setRouter(Router::getInstance());
		}
		
		return $this->_router;
	}
	
	/**
	 * Setter for request
	 * @param \Vzed\Request $request
	 * @return $this
	 */
	private function _setRequest($request) {
		$this->_request	= $request;
		return $this;
	}
	
	/**
	 * Getter for request property
	 */
	public function getRequest() {
		return $this->_request;
	}
	
	/**
	 * Static Getter for request property
	 */
	public static function request() {
		return self::getInstance()->request();
	}
	
}

?>