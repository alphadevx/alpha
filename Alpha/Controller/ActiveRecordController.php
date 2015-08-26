<?php

namespace Alpha\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Helper\Validator;
use Alpha\View\View;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\Model\ActiveRecord;

/**
 * The main active record CRUD controller for the framework.
 *
 * @since 2.0
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
 *
 */
class ActiveRecordController extends Controller implements ControllerInterface
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 2.0
     */
    private static $logger = null;

    /**
     * Constructor to set up the object
     *
     * @param string $visibility The name of the rights group that can access this controller.
     * @since 1.0
     */
    public function __construct($visibility='Admin')
    {
        self::$logger = new Logger('ActiveRecordController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct($visibility);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\ResourceNotFoundException
     * @throws Alpha\Exception\IllegalArguementException
     * @return Alpha\Util\Http\Response
     * @since 2.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();
        $accept = $request->getAccept();

        $body = '';

        try {
            // get a single record
            if (isset($params['ActiveRecordType']) && isset($params['ActiveRecordOID'])) {
                if (!Validator::isInteger($params['ActiveRecordOID'])) {
                    throw new IllegalArguementException('Invalid oid ['.$params['ActiveRecordOID'].'] provided on the request!');
                }

                $ActiveRecordType = urldecode($params['ActiveRecordType']);

                if (class_exists($ActiveRecordType)) {
                    $record = new $ActiveRecordType();
                } else {
                    throw new IllegalArguementException('No ActiveRecord available to view!');
                }

                $record->load($params['ActiveRecordOID']);
                ActiveRecord::disconnect();

                $view = View::getInstance($record, false, $accept);

                $body .= View::displayPageHead($this);
                $body .= View::renderDeleteForm($request->getURI());
                $body .= $view->detailedView();
            } elseif (isset($params['ActiveRecordType'])) {
                // TODO list all records of this type
            } else {
                throw new IllegalArguementException('No ActiveRecord available to display!');
            }
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The file that you have requested cannot be found!');
        } catch (BONotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The item that you have requested cannot be found!');
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
        return new Response(200, $body, array('Content-Type' => ($accept == 'application/json' ? 'application/json' : 'text/html')));
    }

    /**
     * Method to handle POST requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 2.0
     * @todo implement
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doDPOST(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        self::$logger->debug('<<doPOST');
        return new Response(201, $body, array('Content-Type' => 'application/json'));
    }

    /**
     * Method to handle PUT requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 2.0
     * @todo implement
     */
    public function doPUT($request)
    {
        self::$logger->debug('>>doPUT(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        self::$logger->debug('<<doPUT');
        return new Response(200, $body, array('Content-Type' => 'application/json'));
    }

    /**
     * Method to handle DELETE requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 2.0
     * @todo implement
     */
    public function doDELETE($request)
    {
        self::$logger->debug('>>doDELETE(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        self::$logger->debug('<<doDELETE');
        return new Response(200, $body, array('Content-Type' => 'application/json'));
    }
}

?>