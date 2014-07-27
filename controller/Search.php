<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();

	require_once $config->get('app.root').'alpha/util/AlphaAutoLoader.inc';
}

/**
 *
 * Generic tag-based search engine controller
 *
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2014, John Collins (founder of Alpha Framework).
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
			$log = new LogFile($config->get('app.file.store.dir').'logs/search.log');
			$log->writeLine(array($params['q'], date('Y-m-d H:i:s'), $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']));

			$KPI->logStep('log search query');

			$provider = SearchProviderFactory::getInstance('SearchProviderTags');

			// if a BO name is provided, only search tags on that class, otherwise search all BOs
			if(isset($params['bo']))
        		$results = $provider->search($params['q'], $params['bo'], $this->startPoint);
        	else
        		$results = $provider->search($params['q'], 'all', $this->startPoint);

        	$this->resultCount = $provider->getNumberFound();

        	$KPI->logStep('search completed using SearchProviderTags provider');

        	$this->renderResultList($results, $params['q']);

		}else{
			$this->setTitle('Search results');
			echo AlphaView::displayPageHead($this);
			self::$logger->debug('No search query provided!');
		}

		echo AlphaView::displayPageFoot($this);

		$KPI->log();

		self::$logger->debug('<<doGET');
	}

	/**
	 * Renders the search result list
	 *
	 * @param array $results
	 * @param string $query
	 * @param bool $showTags
	 * @since 1.0
	 */
	protected function renderResultList($results, $query='', $showTags=true) {
		global $config;

		// used to track when our pagination range ends
		$end = ($this->startPoint+$config->get('app.list.page.amount'));

		if(!empty($query))
			echo '<h2>Displaying results for &quot;'.$query.'&quot;</h2>';

		foreach($results as $bo) {

			if($bo instanceof ArticleObject && $bo->get('published') == false){
				$this->resultCount--;
			}else{
				$view = AlphaView::getInstance($bo);
				echo $view->listView();

				if($showTags) {
					$tags = $bo->getPropObject('tags')->getRelatedObjects();

					if(count($tags) > 0) {
						echo '<p>Tags: ';

						$queryTerms = explode(' ', mb_strtolower($query));

						foreach($tags as $tag) {
							echo (in_array($tag->get('content'), $queryTerms) ? '<strong>'.$tag->get('content').' </strong>' : $tag->get('content').' ');
						}

						echo '</p>';
					}
				}
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

		$html = '<div align="center"><form method="GET" id="search_form" onsubmit="document.location = \''.$config->get('app.url').'search/q/\'+document.getElementById(\'q\').value; return false;">';
		$html .= 'Search for: <input type="text" size="80" name="q" id="q"/>&nbsp;';
		$button = new Button('document.location = \''.$config->get('app.url').'search/q/\'+document.getElementById(\'q\').value', 'Search', 'searchButton');
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

		$end = ($this->startPoint+$config->get('app.list.page.amount'));

		if($end > $this->resultCount)
			$end = $this->resultCount;

		if($this->resultCount > 0) {
			$html .= '<p align="center">Displaying '.($this->startPoint+1).' to '.$end.' of <strong>'.$this->resultCount.'</strong>.&nbsp;&nbsp;';
		}else{
			if(!empty($this->query))
				$html .= AlphaView::displayUpdateMessage('There were no search results for your query.');
		}

		$html .= '<ul class="pagination">';

		if ($this->startPoint > 0) {
			// handle secure URLs
			if(isset($_GET['tk']))
				$html .= '<li><a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.($this->startPoint-$config->get('app.list.page.amount'))).'">&laquo;</a></li>';
			else
				$html .= '<li><a href="'.$config->get('app.url').'search/q/'.$this->query.'/start/'.($this->startPoint-$config->get('app.list.page.amount')).'">&laquo;</a></li>';
		}elseif($this->resultCount > $config->get('app.list.page.amount')){
			$html .= '<li class="disabled"><a href="#">&laquo;</a></li>';
		}
		$page = 1;
		for ($i = 0; $i < $this->resultCount; $i+=$config->get('app.list.page.amount')) {
			if($i != $this->startPoint) {
				// handle secure URLs
				if(isset($_GET['tk']))
					$html .= '<li><a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.$i).'">'.$page.'</a></li>';
				else
					$html .= '<li><a href="'.$config->get('app.url').'search/q/'.$this->query.'/start/'.$i.'">'.$page.'</a></li>';
			}elseif($this->resultCount > $config->get('app.list.page.amount')){
				$html .= '<li class="active"><a href="#">'.$page.'</a></li>';
			}
			$page++;
		}
		if ($this->resultCount > $end) {
			// handle secure URLs
			if(isset($_GET['tk']))
				$html .= '<li><a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.($this->startPoint+$config->get('app.list.page.amount'))).'">Next-&gt;&gt;</a></li>';
			else
				$html .= '<li><a href="'.$config->get('app.url').'search/q/'.$this->query.'/start/'.($this->startPoint+$config->get('app.list.page.amount')).'">&raquo;</a></li>';
		}elseif($this->resultCount > $config->get('app.list.page.amount')){
			$html .= '<li class="disabled"><a href="#">&raquo;</a></li>';
		}
		$html .= '</ul>';
		$html .= '</p>';

		return $html;
	}

	/**
	 * Get the search result count
	 *
	 * @return int
	 * @since 1.2.4
	 */
	public function getResultCount() {
		return $this->resultCount;
	}

	/**
	 * Set the search result count
	 *
	 * @param int $resultCount
	 * @since 1.2.4
	 */
	protected function setResultCount($resultCount) {
		$this->resultCount = $resultCount;
	}

	/**
	 * Get the search query
	 *
	 * @return string
	 * @since 1.2.4
	 */
	public function getSearchQuery() {
		return $this->query;
	}

	/**
	 * Set the search query
	 *
	 * @param string $query
	 * @since 1.2.4
	 */
	protected function setSearchQuery($query) {
		$this->query = $query;
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