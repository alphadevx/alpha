<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\View\View;
use Alpha\View\Widget\SmallTextBox;
use Alpha\View\Widget\Button;
use Alpha\Controller\Front\FrontController;
use Alpha\Model\Type\SmallText;

/**
 * Controller used to generate secure URLs from the query strings provided.
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
class GenSecureQueryStringController extends Controller implements ControllerInterface
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
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('GenSecureQueryStringController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        $this->setTitle('Generate Secure Query Strings');

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
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $body = View::displayPageHead($this);

        $body .= $this->renderForm();

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle POST requests.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doPOST($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = View::displayPageHead($this);

        $body .= '<p class="alert alert-success">';
        if (isset($params['QS'])) {
            $body .= FrontController::generateSecureURL($params['QS']);
            self::$logger->action('Generated the secure URL in admin: '.FrontController::generateSecureURL($params['QS']));
        }
        $body .= '</p>';

        $body .= $this->renderForm();

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doPOST');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Renders the HTML form for generating secure URLs.
     *
     * @return string
     *
     * @since 1.0
     */
    private function renderForm()
    {
        $config = ConfigProvider::getInstance();

        $html = '<p>Use this form to generate secure (encrypted) URLs which make use of the Front Controller.  Always be sure to specify an action controller'.
            ' (act) at a minimum.</p>';
        $html .= '<p>Example 1: to generate a secure URL for viewing article object 00000000001, enter <em>act=Alpha\Controller\ArticleController&amp;ActiveRecordOID=00000000001</em></p>';
        $html .= '<p>Example 2: to generate a secure URL for viewing an Atom news feed of the articles, enter'.
            ' <em>act=Alpha\Controller\FeedController&amp;ActiveRecordType=Alpha\Model\Article&amp;type=Atom</em></p>';

        $html .= '<form action="'.$this->request->getURI().'" method="post" accept-charset="UTF-8"><div class="form-group">';
        $string = new SmallTextBox(new SmallText(''), 'Parameters', 'QS');
        $html .= $string->render();
        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('saveBut')) : 'saveBut');
        $temp = new Button('submit', 'Generate', $fieldname);
        $html .= $temp->render();
        $html .= '</div></form>';

        return $html;
    }
}
