<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';

/**
* 
* Controller used to list all of the threads in the forum
* 
* @package Alpha Core Forum
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*
*/
class list_threads extends Controller
{
	/**
	 * the constructor
	 */
	function list_threads() {		
		// ensure that the super class constructor is called
		$this->Controller();
		
		// set up the title and meta details
		$this->set_title("Listing all forum threads");
		$this->set_description("Page to list all of the forum threads.");
		$this->set_keywords("list,all,forum,threads");
		
		$this->display_page_head();
		
		echo '<p class="warning">The user forum is currently being built and is planned for release with a future version of this web site very soon!</p>';
		
		$this->display_page_foot();
	}	
}

// now build the new controller
$controller = new list_threads();

?>
