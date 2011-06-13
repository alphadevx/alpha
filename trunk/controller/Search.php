<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/model/TagObject.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/util/LogFile.inc';
require_once $config->get('sysRoot').'alpha/util/AlphaKPI.inc';

/**
 * 
 * Generic tag-based search engine controller
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class Search extends AlphaController implements AlphaControllerInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * The start number for list pageination
	 * 
	 * @var integer
	 * @since 1.0
	 */
	protected $startPoint;
	
	/**
	 * The result count from the search
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $resultCount = 0;
	
	/**
	 * The search query supplied
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $query;
	
	/**
	 * constructor to set up the object
	 * 
	 * @param string $visibility The name of the rights group that can access this controller.
	 * @since 1.0
	 */
	public function __construct($visibility='Public') {
		self::$logger = new Logger('Search');
		self::$logger->debug('>>__construct(visibility=['.$visibility.'])');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct($visibility);
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');
		
		if (isset($params['start']) ? $this->startPoint = $params['start']: $this->startPoint = 0);
		
		global $config;
		
		$KPI = new AlphaKPI('search');
		
		if(isset($params['q'])) {
			
			$this->query = $params['q'];
			
			// replace any %20 on the URL with spaces
			$params['q'] = str_replace('%20', ' ', $params['q']);
			
			$this->setTitle('Search results - '.$params['q']);			
			echo AlphaView::displayPageHead($this);
			
			// log the user's search query in a log file
			$log = new LogFile($config->get('sysRoot').'logs/search.log');		
			$log->writeLine(array($params['q'], date('Y-m-d H:i:s'), $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']));
			
			$KPI->logStep('log search query');
			
			// if a BO name is provided, only search tags on that class, otherwise search all BOs
			if(isset($params['bo']))
				$BOs = array($params['bo']);
			else			
				$BOs = AlphaDAO::getBOClassNames();
			
			try {
				foreach($BOs as $BO) {
					AlphaDAO::loadClassDef($BO);
					$temp = new $BO;
					
					if($temp->isTagged()) {
						// explode the user's query into a set of tokenized transient TagObjects
						$queryTags = TagObject::tokenize($params['q'], '', '', false);			
						$matchingTags = array();
						
						// load TagObjects from the DB where content equals the content of one of our transient TagObjects
						foreach($queryTags as $queryTag) {
							$tags = $queryTag->loadAllByAttribute('content', $queryTag->get('content'));
							$matchingTags = array_merge($matchingTags, $tags);
						}
						
						self::$logger->debug('There are ['.count($matchingTags).'] TagObjects matching the query ['.$params['q'].']');
						
						$KPI->logStep('loaded matching tags');
						
						/*
						 * Build an array of BOs for the matching tags from the DB:
						 * array key = BO ID
						 * array value = weight (the amount of tags matching the BO)
						 */
						$BOIDs = array();
						
						foreach($matchingTags as $tag) {							
							if($tag->get('taggedClass') == $BO) {
								if(isset($BOIDs[$tag->get('taggedOID')])) {
									// increment the weight if the same BO is tagged more than once
									$weight = intval($BOIDs[$tag->get('taggedOID')]) + 1;
									$BOIDs[$tag->get('taggedOID')] = $weight;									
								}else{
									$BOIDs[$tag->get('taggedOID')] = 1;									
								}
								self::$logger->debug('Found BO ['.$tag->get('taggedOID').'] has weight ['.$BOIDs[$tag->get('taggedOID')].']');								
							}
						}
						
						$this->resultCount += count($BOIDs);
						
						$KPI->logStep('built array of matching OIDs');
						
						// sort the BO IDs based on tag frequency weight						
						arsort($BOIDs);						
						
						// render the list view for each BO
						$this->renderResultList($BOIDs, $BO, $params['q']);						
						
						AlphaDAO::disconnect();
					}
				}
			}catch(Exception $e) {
				self::$logger->error($e->getMessage());
				throw new IllegalArguementException('Error occured while searching for: ['.$params['q'].']');				
			}
		}else{
			$this->setTitle('Search results');			
			echo AlphaView::displayPageHead($this);
			self::$logger->warn('No search query provided!');
		}		
		
		echo AlphaView::displayPageFoot($this);
		
		$KPI->log();
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Renders the search result list
	 * 
	 * @param array $BOIDs
	 * @param string $BO
	 * @param string $query
	 * @since 1.0
	 */
	protected function renderResultList($BOIDs, $BO, $query) {
		global $config;
		
		// used to track when our pagination range ends
		$end = ($this->startPoint+$config->get('sysListPageAmount'));
		// used to track how many results have been displayed or skipped from the pagination range
		$displayedCount = 0;
		
		echo '<h2>Displaying results for &quot;'.$query.'&quot;</h2>';
			
		foreach(array_keys($BOIDs) as $oid) {
			try {
				// if we have reached the end of the pagination range then break out
				if($displayedCount == $end)
					break;
							
				// if our display count is >= the start but < the end...
				if($displayedCount >= $this->startPoint) {
					$temp = new $BO;
					$temp->load($oid);
					
					if($temp instanceof ArticleObject && $temp->get('published') == false){
						$this->resultCount--;
					}else{			
						$view = AlphaView::getInstance($temp);
						echo $view->listView();
										
						$tags = $temp->getPropObject('tags')->getRelatedObjects();
					
						if(count($tags) > 0) {
							echo '<p>Tags: ';
											
							$queryTerms = explode(' ', strtolower($query));
											
							foreach($tags as $tag) {
								echo (in_array($tag->get('content'), $queryTerms) ? '<strong>'.$tag->get('content').' </strong>' : $tag->get('content').' ');
							}
										
							echo '</p>';
						}
					}
				}
							
				$displayedCount++;
			}catch(BONotFoundException $e) {
				self::$logger->warn('Orpaned TagObject detected pointing to a non-existant BO of OID ['.$oid.'] and type ['.$BO.'].');
			}
		}
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Displays a search form on the top of the page
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function after_displayPageHead_callback() {
		global $config;
		
		$html = '<div align="center"><form method="GET" id="search_form" onsubmit="document.location = \''.$config->get('sysURL').'search/q/\'+document.getElementById(\'q\').value; return false;">';
		$html .= 'Search for: <input type="text" size="80" name="q" id="q"/>&nbsp;';		
		$button = new Button('document.location = \''.$config->get('sysURL').'search/q/\'+document.getElementById(\'q\').value', 'Search', 'searchButton');
		$html .= $button->render();
		$html .= '</form></div>';
		
		return $html;
	}
	
	/**
	 * Method to display the page footer with pageination links
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function before_displayPageFoot_callback() {
		$html = $this->renderPageLinks();
		
		$html .= '<br>';
		
		return $html;
	}
	
	/**
	 * Method for rendering the pagination links
	 * 
	 * @return string
	 * @since 1.0
	 */
	protected function renderPageLinks() {
		global $config;
		
		$html = '';
		
		$end = ($this->startPoint+$config->get('sysListPageAmount'));
		
		if($end > $this->resultCount)
			$end = $this->resultCount;
		
		if($this->resultCount > 0)
			$html .= '<p align="center">Displaying '.($this->startPoint+1).' to '.$end.' of <strong>'.$this->resultCount.'</strong>.&nbsp;&nbsp;';
		else
			$html .= AlphaView::displayUpdateMessage('There were no search results for your query.');	
				
		if ($this->startPoint > 0) {
			// handle secure URLs
			if(isset($_GET['tk']))
				$html .= '<a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.($this->startPoint-$config->get('sysListPageAmount'))).'">&lt;&lt;-Previous</a>&nbsp;&nbsp;';
			else
				$html .= '<a href="'.$config->get('sysURL').'search/q/'.$this->query.'/start/'.($this->startPoint-$config->get('sysListPageAmount')).'">&lt;&lt;-Previous</a>&nbsp;&nbsp;';
		}elseif($this->resultCount > $config->get('sysListPageAmount')){
			$html .= '&lt;&lt;-Previous&nbsp;&nbsp;';
		}
		$page = 1;
		for ($i = 0; $i < $this->resultCount; $i+=$config->get('sysListPageAmount')) {
			if($i != $this->startPoint) {
				// handle secure URLs
				if(isset($_GET['tk']))
					$html .= '&nbsp;<a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.$i).'">'.$page.'</a>&nbsp;';
				else
					$html .= '&nbsp;<a href="'.$config->get('sysURL').'search/q/'.$this->query.'/start/'.$i.'">'.$page.'</a>&nbsp;';
			}elseif($this->resultCount > $config->get('sysListPageAmount')){
				$html .= '&nbsp;'.$page.'&nbsp;';
			}
			$page++;
		}
		if ($this->resultCount > $end) {
			// handle secure URLs
			if(isset($_GET['tk']))
				$html .= '&nbsp;&nbsp;<a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.($this->startPoint+$config->get('sysListPageAmount'))).'">Next-&gt;&gt;</a>';
			else
				$html .= '&nbsp;&nbsp;<a href="'.$config->get('sysURL').'search/q/'.$this->query.'/start/'.($this->startPoint+$config->get('sysListPageAmount')).'">Next-&gt;&gt;</a>';
		}elseif($this->resultCount > $config->get('sysListPageAmount')){
			$html .= '&nbsp;&nbsp;Next-&gt;&gt;';
		}
		$html .= '</p>';
		
		return $html;
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'Search.php') {
	$controller = new Search();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>