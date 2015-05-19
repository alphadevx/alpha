<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\View\View;
use Alpha\View\PersonView;
use Alpha\Model\Person;
use Alpha\Model\ActiveRecord;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\ValidationException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Controller\Front\FrontController;

/**
 * Login controller that adds the current user object to the session
 *
 * @since 1.0
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
class LoginController extends Controller implements ControllerInterface
{
    /**
     * The person to be logged in
     *
     * @var Alpha\Model\Person
     * @since 1.0
     */
    protected $personObject;

    /**
     * The person view object
     *
     * @var Alpha\View\PersonView
     * @since 1.0
     */
    private $personView;

    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * constructor to set up the object
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('Login');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Public');

        $this->personObject = new Person();
        $this->personView = View::getInstance($this->personObject);
        $this->setBO($this->personObject);

        // set up the title and meta details
        $this->setTitle('Login to '.$config->get('app.title'));
        $this->setDescription('Login page.');
        $this->setKeywords('login,logon');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param Alpha\Util\Http\Request $request
     * @return Alpha\Util\Http\Response
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        if (!is_array($params))
            throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doGET method!');

        $body = View::displayPageHead($this);

        if (isset($params['reset']))
            $body .= $this->personView->displayResetForm();
        else
            $body .= $this->personView->displayLoginForm();

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle POST requests (adds $currentUser Person to the session)
     *
     * @param Alpha\Util\Http\Request $request
     * @return Alpha\Util\Http\Response
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doPOST($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        if (!is_array($params))
            throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doPOST method!');

        $config = ConfigProvider::getInstance();

        $body = '';

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields())
                throw new SecurityException('This page cannot accept post data from remote servers!');

            if (isset($params['loginBut'])) {
                // if the database has not been set up yet, accept a login from the config admin username/password
                if (!ActiveRecord::isInstalled()) {
                    if ($params['email'] == $config->get('app.install.username') && crypt($params['password'], $config->get('app.install.password')) ==
                        crypt($config->get('app.install.password'), $config->get('app.install.password'))) {

                        self::$logger->info('Logging in ['.$params['email'].'] at ['.date("Y-m-d H:i:s").']');
                        $admin = new Person();
                        $admin->set('displayName', 'Admin');
                        $admin->set('email', $params['email']);
                        $admin->set('password', crypt($params['password'], $config->get('app.install.password')));
                        $admin->set('OID', '00000000001');

                        $sessionProvider = $config->get('session.provider.name');
                        $session = SessionProviderFactory::getInstance($sessionProvider);
                        $session->set('currentUser', $admin);

                        $response = new Response(301);
                        if ($this->getNextJob() != '')
                            $response->redirect($this->getNextJob());
                        else
                            $response->redirect(FrontController::generateSecureURL('act=Install'));

                        return $response;
                    } else {
                        throw new ValidationException('Failed to login user '.$params['email'].', the password is incorrect!');
                    }
                } else {
                    // here we are attempting to load the person from the email address
                    $this->personObject->loadByAttribute('email', $params['email'], true);

                    ActiveRecord::disconnect();

                    // checking to see if the account has been disabled
                    if (!$this->personObject->isTransient() && $this->personObject->get('state') == 'Disabled')
                        throw new SecurityException('Failed to login user '.$params['email'].', that account has been disabled!');

                    // check the password
                    return $this->doLoginAndRedirect($params['password']);
                }

                $body .= View::displayPageHead($this);

                $body .= $this->personView->displayLoginForm();
            }

            if (isset($params['resetBut'])) {
                // here we are attempting to load the person from the email address
                $this->personObject->loadByAttribute('email', $params['email']);

                ActiveRecord::disconnect();

                // generate a new random password
                $new_password = $this->personObject->generatePassword();

                // now encrypt and save the new password, then e-mail the user
                $this->personObject->set('password', crypt($new_password));
                $this->personObject->save();

                $message = 'The password for your account has been reset to '.$new_password.' as you requested.  You can now login to the site using your '.
                    'e-mail address and this new password as before.';
                $subject = 'Password change request';

                $this->personObject->sendMail($message, $subject);

                $body .= View::displayUpdateMessage('The password for the user <strong>'.$params['email'].'</strong> has been reset, and the new password '.
                    'has been sent to that e-mail address.');
                $body .= '<a href="'.$config->get('app.url').'">Home Page</a>';
            }
        } catch (ValidationException $e) {
            $body .= View::displayPageHead($this);

            $body .= View::displayErrorMessage($e->getMessage());

            if (isset($params['reset']))
                $body .= $this->personView->displayResetForm();
            else
                $body .= $this->personView->displayLoginForm();

            self::$logger->warn($e->getMessage());
        } catch (SecurityException $e) {
            $body .= View::displayPageHead($this);

            $body .= View::displayErrorMessage($e->getMessage());

            self::$logger->warn($e->getMessage());
        }catch(RecordNotFoundException $e) {
            $body .= View::displayPageHead($this);

            $body .= View::displayErrorMessage('Failed to find the user \''.$params['email'].'\'');

            if (isset($params['reset']))
                $body .= $this->personView->displayResetForm();
            else
                $body .= $this->personView->displayLoginForm();

            self::$logger->warn($e->getMessage());
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doPOST');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Login the user and re-direct to the defined destination
     *
     * @param string $password The password supplied by the user logging in
     * @throws Alpha\Exception\ValidationException
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    protected function doLoginAndRedirect($password)
    {
        self::$logger->debug('>>doLoginAndRedirect(password=['.$password.'])');

        $config = ConfigProvider::getInstance();

        if (!$this->personObject->isTransient() && $this->personObject->get('state') == 'Active') {
            if (crypt($password, $this->personObject->get('password')) == $this->personObject->get('password')) {

                $session = SessionProviderFactory::getInstance($sessionProvider);
                $session->set('currentUser', $this->personObject);

                self::$logger->debug('Logging in ['.$this->personObject->get('email').'] at ['.date("Y-m-d H:i:s").']');
                self::$logger->action('Login');

                $response = new Response(301);
                if ($this->getNextJob() != '')
                    $response->redirect($this->getNextJob());
                else
                    $response->redirect($config->get('app.url'));

                return $response;
            } else {
                throw new ValidationException('Failed to login user '.$this->personObject->get('email').', the password is incorrect!');
                self::$logger->debug('<<doLoginAndRedirect');
            }
        }
    }

    /**
     * Displays the application version number on the login screen.
     *
     * @return string
     * @since 1.0
     */
    public function before_displayPageFoot_callback()
    {
        $config = ConfigProvider::getInstance();

        return '<p><em>Version '.$config->get('app.version').'</em></p>';
    }
}

?>