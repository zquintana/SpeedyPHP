<?php 
return <<<EOF
<?php

use \Speedy\Loader;
use \Speedy\Session;

class App extends \Speedy\App {

	protected \$_name = "{$namespace}";
	
	protected \$_orm	= \Speedy\Orm\ActiveRecord;


	protected function initApp() {
		Session::start();
	}
	
}

?>
EOF;
?>