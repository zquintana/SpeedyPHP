<?php 
namespace Speedy\Router\Routes;

abstract class Base {
	
	protected $_params = array();
	
	protected $_request;
	
	protected $_route;
	
	protected $_format;
	
	protected $_options;
	
	protected $_compiledRoute;
	
	/**
	 * Name for use in layouts
	 * @var string
	 */
	protected $_name;
	
	/**
	 * Flag to determin if the route is greedy
	 * @var boolean
	 */
	protected $_greedy;
	
	/**
	 * Regex pattern for route
	 * @var string
	 */
	protected $_pattern	= null;
	
	/**
	 * Route tokens from format
	 * @var array
	 */
	protected $_tokens = null;
	
	
	
	
	/**
	 * Setter for route
	 */
	//abstract public function setRoute();
	
	/**
	 * Checks if route matches request
	 * @param Speedy\Request $request
	 * @return boolean
	 */
	abstract public function match($request);
	
	/**
	 * Returns route for request
	 * @param Speedy\Request $request
	 */
	abstract public function route();
	
	/**
	 * Getter for name
	 * @return string
	 */
	public function name() {
		return $this->_name;
	}
	
	/**
	 * Getter for pattern
	 * @return string
	 */
	public function pattern() {
		if ($this->_pattern == null) {
			$this->processFormat();
		}
		
		return $this->_pattern;
	}
	
	/**
	 * Setter for request
	 * @param Speedy\Request $request
	 * @return Speedy\Route
	 */
	public function setRequest(\Speedy\Request $request) {
		$this->_request = $request;
		
		return $this;
	}
	
	/**
	 * Getter for request
	 * @return \Speedy\Request
	 */
	public function request() {
		if (!$this->_request) {
			throw new Exception("Request not set in route");
		}
		
		return $this->_request;
	}
	
	/**
	 * Getter for options property
	 */
	public function options() {
		return (is_array($this->_options)) ? $this->_options : array();
	}
	
	/**
	 * Getter for a specific options
	 * @param int/string $name
	 */
	public function option($name) {
		return (!empty($this->_options[$name])) ? $this->_options[$name] : null;
	}
	
	/**
	 * Getter for format
	 */
	public function format() {
		return $this->_format;
	}
	
	/**
	 * Getter for params
	 * @return array params
	 */
	public function params() {
		return $this->_params;
	}
	
	/**
	 * Getter for greedy
	 * @return boolean
	 */
	public function greedy() {
		return $this->_greedy;
	}
	
	/**
	 * Getter for greedy
	 * @return mixed
	 */
	public function token($index = null) {
		if ($this->_tokens == null) {
			$this->processFormat();
		}
		
		return ($index === null) ? $this->_tokens : $this->_tokens[$index];
	}
	
	/**
	 * Used to find a match
	 * @param \Speedy\Request $request
	 */
	public function isMatch(\Speedy\Request $request) {
		$on	= $this->option('on');
		if ($on && strtolower($on) != strtolower($request->method())) return false;
		
		return $this->match($request);
	}
	
	/**
	 * Add param
	 * @param string $key
	 * @param mixed $value
	 * @return \Speedy\Router\Routes\Base
	 */
	protected function addParam($key, $value) {
		$this->_params[$key]	= $value;
		return $this;
	}
	
	/**
	 * Setter for name
	 * @param string $name
	 * @return \Speedy\Router\Routes\Route
	 */
	protected function setName($name) {
		$this->_name	= $name;
		return $this;
	}
	
	/**
	* Setter for options
	* @param array $options
	*/
	protected function setOptions($options) {
		$this->_options = $options;
		return $this;
	}
	
	/**
	 * Setter for format
	 * @param string $format
	 */
	protected function setFormat($format) {
		$this->_format	= $format;
		return $this;
	}
	
	/**
	 * Setter for params
	 * @param array $params
	 * @return Speedy\Router\Routes\Route
	 */
	protected function setParams(array $params) {
		unset($params['on']);
		asort($params);
		
		$this->_params = $params;
		
		return $this;
	}
	
	/**
	 * Setter for greedy
	 * @param boolean $greedy
	 * @return \Speedy\Router\Routes\Route
	 */
	protected function setGreedy($greedy) {
		$this->_greedy	= $greedy;
		return $this;
	}
	
	/**
	 * Setter for tokens
	 * @param array $tokens
	 * @return Match
	 */
	protected function setTokens(array $token) {
		$this->_tokens	= $token;
		return $this;
	}
	
	/**
	 * Setter for pattern
	 * @param string $pattern
	 * @return \Speedy\Router\Routes\Route
	 */
	protected function setPattern($pattern) {
		$this->_pattern	= $pattern;
		return $this;
	}
	
	/**
	 * Processes the format 
	 * @return \Speedy\Router\Router\Routes\Route
	 */
	protected function processFormat() {
		$format	= $this->format();
		
		// is the format greedy and get tokens
		// then loop matches to build regex match
		// for matching the format to the uri
		$this->setGreedy((strpos($format, '*') === strlen($format) - 1) ? true : false);
		preg_match_all('#:?([A-Za-z0-9]+[\_\-A-Z0-9a-z]*)#', $format, $matches);
		
		$tokens	= array();
		$regex	= "#";
		$i = 0; 
		foreach($matches[0] as $match) {
			if ($i) $regex	.= '/';
			else $regex .= '^/';
		
			// if the part starts with colon then it's a token and add it as such
			if (preg_match("#^:#", $match)) {
				$regex		.= "([A-Za-z0-9_\-]+[A-Z0-9a-z]*)";
				$tokens[]	= substr_replace($match, '', 0, 1);
			} else {
				$regex		.= $match;
			}
		
			$i++;
		}
		// Add the regex end if it's not greedy
		if (!$this->greedy()) $regex .= '$';
		$regex	.= '#';
		
		$this->setTokens($tokens)->setPattern($regex);
		return $this;
	}
	
}

?>
