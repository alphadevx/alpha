<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Util\Http\Response;
use Alpha\Model\Person;
use Alpha\View\View;

/**
 * Logout controller that removes the current user object from the session.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
class LogoutController extends Controller implements ControllerInterface
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
        self::$logger = new Logger('LogoutController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Public');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        if ($session->get('currentUser') !== false) {
            $this->setRecord($session->get('currentUser'));
        } else {
            self::$logger->warn('Logout controller called when no user is logged in');
        }

        // set up the title and meta details
        $this->setTitle('Logged out successfully.');
        $this->setDescription('Logout page.');
        $this->setKeywords('Logout,logon');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     */
    public function doGET($request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        if ($this->record instanceof Person) {
            self::$logger->debug('Logging out ['.$this->record->get('email').'] at ['.date('Y-m-d H:i:s').']');
            self::$logger->action('Logout');
        }

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->destroy();

        $body = View::displayPageHead($this);

        $body .= View::displayUpdateMessage('You have successfully logged out of the system.');

        $body .= '<div align="center"><a href="'.$config->get('app.url').'">Home Page</a></div>';

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }
}
