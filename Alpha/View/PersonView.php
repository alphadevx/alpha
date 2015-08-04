<?php

namespace Alpha\View;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Http\Request;
use Alpha\Controller\Front\FrontController;
use Alpha\Model\Type\String;
use Alpha\View\Widget\StringBox;
use Alpha\View\Widget\Button;

/**
 *
 * The rendering class for the Person class
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
class PersonView extends View
{
    /**
     * Method to render the login HTML form
     *
     * @return string
     * @since 1.0
     */
    public function displayLoginForm()
    {
        $config = ConfigProvider::getInstance();

        $html = '<div class="bordered padded">';
        $html .= "<h1>Login</h1>";
        $html .= '<form action="'.FrontController::generateSecureURL('act=Alpha\Controller\LoginController').'" method="POST" id="loginForm" accept-charset="UTF-8">';

        $request = new Request(array('method' => 'GET'));
        $email = new String($request->getParam('email', ''));
        $email->setRule(Validator::REQUIRED_EMAIL);
        $email->setSize(70);
        $email->setHelper('Please provide a valid e-mail address!');
        $stringBox = new StringBox($email, $this->BO->getDataLabel('email'), 'email', 'loginForm', '50');
        $html .= $stringBox->render();

        $password = new String();
        $password->isPassword();

        $stringBox = new StringBox($password, $this->BO->getDataLabel('password'), 'password', 'loginForm', '50');
        $html .= $stringBox->render();

        $temp = new Button('submit', 'Login', 'loginBut');
        $html .= '<div class="centered">'.$temp->render(80).'</div>';

        $html .= $this->renderSecurityFields();

        $html .= '</form>';

        $html .= '<p><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\LoginController&reset=true').'">Forgotten your password?</a></p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Method to render the reset password HTML form
     *
     * @return string
     * @since 1.0
     */
    public function displayResetForm()
    {
        $config = ConfigProvider::getInstance();

        $html = '<div class="bordered padded">';
        $html .= '<h1>Password reset</h1>';
        $html .= '<p>If you have forgotten your password, you can use this form to have a new password automatically generated and sent to your e-mail address.</p>';
        $html .= '<form action="'.FrontController::generateSecureURL('act=Alpha\Controller\LoginController&reset=true').'" method="POST" id="resetForm" accept-charset="UTF-8">';

        $request = new Request(array('method' => 'GET'));
        $email = new String($request->getParam('email', ''));
        $email->setRule(Validator::REQUIRED_EMAIL);
        $email->setSize(70);
        $email->setHelper('Please provide a valid e-mail address!');
        $stringBox = new StringBox($email, $this->BO->getDataLabel('email'), 'email', 'resetForm', '50');
        $html .= $stringBox->render();

        $html .= '<div class="form-group lower spread">';

        $temp = new Button('submit', 'Reset Password', 'resetBut');
        $html .= $temp->render();

        $temp = new Button("document.location.replace('".$config->get('app.url')."')", 'Cancel', 'cancelBut');
        $html .= $temp->render();

        $html .= '</div>';

        $html .= $this->renderSecurityFields();

        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Method to render the user registration form
     *
     * @return string
     * @since 1.0
     */
    public function displayRegisterForm()
    {
        $config = ConfigProvider::getInstance();

        $request = new Request(array('method' => 'GET'));

        $html = '<p>In order to access this site, you will need to create a user account.  In order to do so, please provide a valid email address below and a password will be sent to your inbox shortly (you can change your password once you log in).</p>';
        $html .= '<table cols="2">';
        $html .= '<form action="'.$request->getURI().'?reset=true" method="POST" accept-charset="UTF-8">';
        $html .= '<tr>';
        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt('displayname'));
        else
            $fieldname = 'displayname';
        $html .= '  <td>Forum name</td> <td><input type="text" name="'.$fieldname.'" size="50" value="'.$request->getParam($fieldname, '').'"/></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt('email'));
        else
            $fieldname = 'email';
        $html .= '  <td>E-mail Address</td> <td><input type="text" name="'.$fieldname.'" size="50" value="'.$request->getParam($fieldname, '').'"/></td>';
        $html .= '</tr>';
        $html .= '<tr><td colspan="2">';
        $temp = new Button("submit","Register","registerBut");
        $html .= $temp->render();
        $html .= '&nbsp;&nbsp;';
        $temp = new Button("document.location.replace('".$config->get('app.url')."')","Cancel","cancelBut");
        $html .= $temp->render();
        $html .= '</td></tr>';

        $html .= $this->renderSecurityFields();

        $html .= '</form>';
        $html .= '</table>';

        return $html;
    }
}

?>