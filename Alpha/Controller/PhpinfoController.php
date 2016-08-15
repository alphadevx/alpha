<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\View\View;
use Alpha\Controller\Front\FrontController;

/**
 * Login controller that adds the current user object to the session.
 *
 * @since 2.0.3
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2016, John Collins (founder of Alpha Framework).
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
class PhpinfoController extends Controller implements ControllerInterface
{
    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 2.0.3
     */
    private static $logger = null;

    /**
     * constructor to set up the object.
     *
     * @since 2.0.3
     */
    public function __construct()
    {
        self::$logger = new Logger('PhpinfoController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        // set up the title and meta details
        $this->setTitle('Information about the PHP installation');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 2.0.3
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        if ($request->getParam('displayphpinfo') != null) {

            ob_start();
            phpinfo();
            $body = ob_get_contents();

        } else {

            $body = View::displayPageHead($this);

            $url = FrontController::generateSecureURL('act=Alpha\Controller\PhpinfoController&displayphpinfo=true');

            $body .= '<iframe src="'.$url.'" style="border:none; overflow-x: scroll; overflow-y: scroll; width:100%; height:100vh;"></iframe>';

            $body .= View::displayPageFoot($this);

        }

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html', 'X-Frame-Options' => 'SAMEORIGIN'));
    }
}