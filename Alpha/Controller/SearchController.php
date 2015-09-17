<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Logging\KPI;
use Alpha\Util\Logging\LogProviderFile;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Search\SearchProviderFactory;
use Alpha\View\View;
use Alpha\View\Widget\Button;
use Alpha\Controller\Front\FrontController;

/**
 * Search engine controller.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
 */
class SearchController extends Controller implements ControllerInterface
{
    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * The start number for list pageination.
     *
     * @var int
     *
     * @since 1.0
     */
    protected $startPoint;

    /**
     * The result count from the search.
     *
     * @var int
     *
     * @since 1.0
     */
    private $resultCount = 0;

    /**
     * The search query supplied.
     *
     * @var string
     *
     * @since 1.0
     */
    private $query;

    /**
     * constructor to set up the object.
     *
     * @param string $visibility The name of the rights group that can access this controller.
     *
     * @since 1.0
     */
    public function __construct($visibility = 'Public')
    {
        self::$logger = new Logger('SearchController');
        self::$logger->debug('>>__construct(visibility=['.$visibility.'])');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct($visibility);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 1.0
     *
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        if (isset($params['start']) ? $this->startPoint = $params['start'] : $this->startPoint = 0);

        $config = ConfigProvider::getInstance();

        $KPI = new KPI('search');

        $body = '';

        if (isset($params['query'])) {
            $this->query = $params['query'];

            // replace any %20 on the URL with spaces
            $params['query'] = str_replace('%20', ' ', $params['query']);

            $this->setTitle('Search results - '.$params['query']);
            $body .= View::displayPageHead($this);

            // log the user's search query in a log file
            $log = new LogProviderFile();
            $log->setPath($config->get('app.file.store.dir').'logs/search.log');
            $log->writeLine(array($params['query'], date('Y-m-d H:i:s'), $request->getUserAgent(), $request->getIP()));

            $KPI->logStep('log search query');

            $provider = SearchProviderFactory::getInstance('Alpha\Util\Search\SearchProviderTags');

            // if a BO name is provided, only search tags on that class, otherwise search all BOs
            if (isset($params['ActiveRecordType'])) {
                $results = $provider->search($params['query'], $params['bo'], $this->startPoint);
            } else {
                $results = $provider->search($params['query'], 'all', $this->startPoint);
            }

            $this->resultCount = $provider->getNumberFound();

            $KPI->logStep('search completed using SearchProviderTags provider');

            $body .= $this->renderResultList($results, $params['query']);
        } else {
            $this->setTitle('Search results');
            $body .= View::displayPageHead($this);
            self::$logger->debug('No search query provided!');
        }

        $body .= View::displayPageFoot($this);

        $KPI->log();

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Renders the search result list.
     *
     * @param array  $results
     * @param string $query
     * @param bool   $showTags
     *
     * @since 1.0
     *
     * @return string
     */
    protected function renderResultList($results, $query = '', $showTags = true)
    {
        $config = ConfigProvider::getInstance();

        // used to track when our pagination range ends
        $end = ($this->startPoint + $config->get('app.list.page.amount'));

        $body = '';

        if (!empty($query)) {
            $body .= '<h2>Displaying results for &quot;'.$query.'&quot;</h2>';
        }

        foreach ($results as $bo) {
            if ($bo instanceof \Alpha\Model\Article && $bo->get('published') == false) {
                --$this->resultCount;
            } else {
                $view = View::getInstance($bo);
                $URI = $this->request->getURI();
                $body .= $view->listView(array('formAction' => $URI));

                if ($showTags) {
                    $tags = $bo->getPropObject('tags')->getRelatedObjects();

                    if (count($tags) > 0) {
                        $body .= '<p>Tags: ';

                        $queryTerms = explode(' ', mb_strtolower($query));

                        foreach ($tags as $tag) {
                            $body .= (in_array($tag->get('content'), $queryTerms) ? '<strong>'.$tag->get('content').' </strong>' : $tag->get('content').' ');
                        }

                        $body .= '</p>';
                    }
                }
            }
        }

        return $body;
    }

    /**
     * Displays a search form on the top of the page.
     *
     * @return string
     *
     * @since 1.0
     */
    public function after_displayPageHead_callback()
    {
        $config = ConfigProvider::getInstance();

        $body = parent::after_displayPageHead_callback();

        $body .= '<div align="center" class="form-group"><form class="form-inline" method="GET" id="search_form" onsubmit="document.location = \''.$config->get('app.url').'search/\'+document.getElementById(\'q\').value; return false;">';
        $body .= '<label for="q">Search for</label><input type="text" name="q" id="q" class="form-control" style="width:50%; margin:10px;"/>';
        $button = new Button('document.location = \''.$config->get('app.url').'/search/\'+document.getElementById(\'q\').value', 'Search', 'searchButton');
        $body .= $button->render();
        $body .= '</p></form></div>';

        return $body;
    }

    /**
     * Method to display the page footer with pageination links.
     *
     * @return string
     *
     * @since 1.0
     */
    public function before_displayPageFoot_callback()
    {
        $body = $this->renderPageLinks();

        $body .= '<br>';

        return $body;
    }

    /**
     * Method for rendering the pagination links.
     *
     * @return string
     *
     * @since 1.0
     */
    protected function renderPageLinks()
    {
        $config = ConfigProvider::getInstance();

        $params = $this->request->getParams();

        $body = '';

        $end = ($this->startPoint + $config->get('app.list.page.amount'));

        if ($end > $this->resultCount) {
            $end = $this->resultCount;
        }

        if ($this->resultCount > 0) {
            $body .= '<p align="center">Displaying '.($this->startPoint + 1).' to '.$end.' of <strong>'.$this->resultCount.'</strong>.&nbsp;&nbsp;';
        } else {
            if (!empty($this->query)) {
                $body .= View::displayUpdateMessage('There were no search results for your query.');
            }
        }

        $body .= '<ul class="pagination">';

        if ($this->startPoint > 0) {
            // handle secure URLs
            if (isset($params['tk'])) {
                $body .= '<li><a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.($this->startPoint - $config->get('app.list.page.amount'))).'">&laquo;</a></li>';
            } else {
                $body .= '<li><a href="'.$config->get('app.url').'/search/'.$this->query.'/start/'.($this->startPoint - $config->get('app.list.page.amount')).'">&laquo;</a></li>';
            }
        } elseif ($this->resultCount > $config->get('app.list.page.amount')) {
            $body .= '<li class="disabled"><a href="#">&laquo;</a></li>';
        }

        $page = 1;

        for ($i = 0; $i < $this->resultCount; $i += $config->get('app.list.page.amount')) {
            if ($i != $this->startPoint) {
                // handle secure URLs
                if (isset($params['tk'])) {
                    $body .= '<li><a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.$i).'">'.$page.'</a></li>';
                } else {
                    $body .= '<li><a href="'.$config->get('app.url').'/search/'.$this->query.'/start/'.$i.'">'.$page.'</a></li>';
                }
            } elseif ($this->resultCount > $config->get('app.list.page.amount')) {
                $body .= '<li class="active"><a href="#">'.$page.'</a></li>';
            }

            ++$page;
        }

        if ($this->resultCount > $end) {
            // handle secure URLs
            if (isset($params['tk'])) {
                $body .= '<li><a href="'.FrontController::generateSecureURL('act=Search&q='.$this->query.'&start='.($this->startPoint + $config->get('app.list.page.amount'))).'">Next-&gt;&gt;</a></li>';
            } else {
                $body .= '<li><a href="'.$config->get('app.url').'/search/'.$this->query.'/start/'.($this->startPoint + $config->get('app.list.page.amount')).'">&raquo;</a></li>';
            }
        } elseif ($this->resultCount > $config->get('app.list.page.amount')) {
            $body .= '<li class="disabled"><a href="#">&raquo;</a></li>';
        }

        $body .= '</ul>';
        $body .= '</p>';

        return $body;
    }

    /**
     * Get the search result count.
     *
     * @return int
     *
     * @since 1.2.4
     */
    public function getResultCount()
    {
        return $this->resultCount;
    }

    /**
     * Set the search result count.
     *
     * @param int $resultCount
     *
     * @since 1.2.4
     */
    protected function setResultCount($resultCount)
    {
        $this->resultCount = $resultCount;
    }

    /**
     * Get the search query.
     *
     * @return string
     *
     * @since 1.2.4
     */
    public function getSearchQuery()
    {
        return $this->query;
    }

    /**
     * Set the search query.
     *
     * @param string $query
     *
     * @since 1.2.4
     */
    protected function setSearchQuery($query)
    {
        $this->query = $query;
    }
}
