<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Model\Type\Sequence;
use Alpha\Model\ActiveRecord;
use Alpha\View\View;

/**
 * Controller used to list all Sequences.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class SequenceController extends ActiveRecordController implements ControllerInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * constructor to set up the object.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('SequenceController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        // set up the title and meta details
        $this->setTitle('Listing all Sequences');
        $this->setDescription('Page to list all Sequences.');
        $this->setKeywords('list,all,Sequences');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @return \Alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $body = View::displayPageHead($this);

        $sequence = new Sequence();

        // make sure that the Sequence tables exist
        if (!$sequence->checkTableExists()) {
            $body .= View::displayErrorMessage('Warning! The Sequence table do not exist, attempting to create it now...');
            $sequence->makeTable();
        }

        // set the start point for the list pagination
        if (isset($params['start']) ? $this->start = $params['start'] : $this->start = 1);

        $records = $sequence->loadAll($this->start);

        ActiveRecord::disconnect();

        $this->recordCount = $sequence->getCount();

        $body .= View::renderDeleteForm($this->request->getURI());

        foreach ($records as $record) {
            $view = View::getInstance($record);
            $body .= $view->listView(array('URI' => $request->getURI()));
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }
}
