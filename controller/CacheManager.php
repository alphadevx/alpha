<?php

// include the config file
if(!isset($config))
	require_once '../util/AlphaConfig.inc';
$config = AlphaConfig::getInstance();

require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/util/FileUtil.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';

/**
 * 
 * Controller used to clear out the CMS cache when required
 * 
 * @author John Collins <john@design-ireland.net>
 * @package alpha::controller
 * @copyright 2009 John Collins
 * @version $Id$
 */
class CacheManager extends Controller implements AlphaControllerInterface {
	/**
	 * Used to set status update messages to display to the user
	 *
	 * @var string
	 */
	private $statusMessage = '';
	
	/**
	 * The root of the cache directory
	 * 
	 * @var string
	 */
	private $dataDir;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * constructor to set up the object
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('CacheManager');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->setTitle('Cache Manager');
		$this->dataDir  = $config->get('sysRoot').'cache/';
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		global $config;
		
		if(!is_array($params)) {
			throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doGET method!');
			self::$logger->debug('<<doGET');
			return;
		}
		
		echo View::displayPageHead($this);
		
		echo '<h2>Listing contents of cache directory: '.$this->dataDir.'</h2>';
		
   		$fileCount = FileUtil::listDirectoryContents($this->dataDir);
   		
   		echo '<h2>Total of '.$fileCount.' files in the cache.</h2>';
   		
   		echo '<form action="'.$_SERVER['PHP_SELF'].(empty($_SERVER['QUERY_STRING'])? '':'?'.$_SERVER['QUERY_STRING']).'" method="POST" name="clearForm">';
   		echo '<input type="hidden" name="clearCache" value="false"/>';
   		$temp = new button("if (confirm('Are you sure you want to delete all files in the cache?')) {document.forms['clearForm']['clearCache'].value = 'true'; document.forms['clearForm'].submit();}", "Clear cache", "clearBut");
   		echo $temp->render();
   		echo View::renderSecurityFields();
   		echo '</form>';
		
		echo View::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Handle POST requests (adds $currentUser person_object to the session)
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
			
			if(!is_array($params)) {
				throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doPOST method!');
				self::$logger->debug('<<doPOST');
				return;
			}

			if (isset($params['clearCache']) && $params['clearCache'] == 'true') {
				try {
					FileUtil::deleteDirectoryContents($this->dataDir);
							
					$this->statusMessage = '<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Update:</strong> Cache contents deleted successfully.</p>
						</div>';
					
					self::$logger->info('Cache contents deleted successfully.');
				}catch (AlphaException $e) {
					self::$logger->error($e->getMessage());
				}				
			}
			
			$this->doGET($params);
		}catch(SecurityException $e) {
			echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> '.$e->getMessage().'</p>
				</div>';
			
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}
		
		echo View::displayPageFoot($this);
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Renders the status message if one is set
	 * 
	 * @return string
	 */
	public function after_displayPageHead_callback() {
		global $config;
		
		$html = '';
				
		if($this->statusMessage != '')
			$html .= $this->statusMessage;		
		
		return $html;
	}
}

// now build the new controller if this file is called directly
if ('CacheManager.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new CacheManager();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_QUERY);
	}else{
		$controller->doGET($_GET);
	}
}

?>