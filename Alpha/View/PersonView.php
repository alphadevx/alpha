<?php

namespace Alpha\View;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Http\Request;
use Alpha\Controller\Front\FrontController;
use Alpha\Model\Type\SmallText;
use Alpha\View\Widget\SmallTextBox;
use Alpha\View\Widget\Button;

/**
 * The rendering class for the Person class.
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
class PersonView extends View
{
    /**
     * Method to render the login HTML form.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @return string
     *
     * @since 1.0
     */
    public function displayLoginForm($fields = array())
    {
        $fields['formAction'] = FrontController::generateSecureURL('act=Alpha\Controller\LoginController');

        $request = new Request(array('method' => 'GET'));
        $email = new SmallText($request->getParam('email', ''));
        $email->setRule(Validator::REQUIRED_EMAIL);
        $email->setSize(70);
        $email->setHelper('Please provide a valid e-mail address!');
        $stringBox = new SmallTextBox($email, $this->record->getDataLabel('email'), 'email', '50');
        $fields['emailBox'] = $stringBox->render();

        $password = new SmallText();
        $password->isPassword();

        $stringBox = new SmallTextBox($password, $this->record->getDataLabel('password'), 'password', '50');
        $fields['passwordBox'] = $stringBox->render();

        $temp = new Button('submit', 'Login', 'loginBut');
        $fields['loginButton'] = $temp->render(80);

        $fields['formSecurityFields'] = $this->renderSecurityFields();

        $fields['resetURL'] = FrontController::generateSecureURL('act=Alpha\Controller\LoginController&reset=true');

        return View::loadTemplate($this->record, 'login', $fields);
    }

    /**
     * Method to render the reset password HTML form.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @return string
     *
     * @since 1.0
     */
    public function displayResetForm($fields = array())
    {
        $config = ConfigProvider::getInstance();

        $fields['formAction'] = FrontController::generateSecureURL('act=Alpha\Controller\LoginController&reset=true');

        $request = new Request(array('method' => 'GET'));
        $email = new SmallText($request->getParam('email', ''));
        $email->setRule(Validator::REQUIRED_EMAIL);
        $email->setSize(70);
        $email->setHelper('Please provide a valid e-mail address!');
        $stringBox = new SmallTextBox($email, $this->record->getDataLabel('email'), 'email', '50');
        $fields['emailBox'] = $stringBox->render();

        $temp = new Button('submit', 'Reset Password', 'resetBut');
        $fields['resetButton'] = $temp->render();

        $temp = new Button("document.location.replace('".$config->get('app.url')."')", 'Cancel', 'cancelBut');
        $fields['cancelButton'] = $temp->render();

        $fields['formSecurityFields'] = $this->renderSecurityFields();

        return View::loadTemplate($this->record, 'reset', $fields);
    }

    /**
     * Method to render the user registration form.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @return string
     *
     * @since 1.0
     */
    public function displayRegisterForm($fields = array())
    {
        $config = ConfigProvider::getInstance();

        $request = new Request(array('method' => 'GET'));

        $fields['formAction'] = $request->getURI().'?reset=true';
        
        if ($config->get('security.encrypt.http.fieldnames')) {
            $fields['usernameFieldname'] = base64_encode(SecurityUtils::encrypt('username'));
        } else {
            $fields['usernameFieldname'] = 'username';
        }
        $fields['username'] = $request->getParam($fields['usernameFieldname'], '');
        
        if ($config->get('security.encrypt.http.fieldnames')) {
            $fields['emailFieldname'] = base64_encode(SecurityUtils::encrypt('email'));
        } else {
            $fields['emailFieldname'] = 'email';
        }
        $fields['email'] = $request->getParam($fields['emailFieldname'], '');
        
        $temp = new Button('submit', 'Register', 'registerBut');
        $fields['registerButton'] = $temp->render();
        
        $temp = new Button("document.location.replace('".$config->get('app.url')."')", 'Cancel', 'cancelBut');
        $fields['cancelButton'] = $temp->render();

        $fields['formSecurityFields'] = $this->renderSecurityFields();

        return View::loadTemplate($this->record, 'register', $fields);
    }
}
