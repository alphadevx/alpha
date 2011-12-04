<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/view/PersonView.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 *
 * Login controller that adds the current user object to the session
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
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
class Login extends AlphaController implements AlphaControllerInterface {
	/**
	 * The person to be logged in
	 * 
	 * @var PersonObject
	 * @since 1.0
	 */
	protected $personObject;
	
	/**
	 * The person view object
	 * 
	 * @var PersonView
	 * @since 1.0
	 */
	private $personView;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * constructor to set up the object
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('Login');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		$this->personObject = new PersonObject();
		$this->personView = AlphaView::getInstance($this->personObject);
		$this->setBO($this->personObject);
		
		// set up the title and meta details
		$this->setTitle('Login to '.$config->get('sysTitle'));
		$this->setDescription('Login page.');
		$this->setKeywords('login,logon');
		
		self::$logger->debug('<<__construct');
	}
		
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');
		
		if(!is_array($params))
			throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doGET method!');
		
		echo AlphaView::displayPageHead($this);
		
		if (isset($params['reset']))
			echo $this->personView->displayResetForm();
		else
			echo $this->personView->displayLoginForm();	
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}	
	
	/**
	 * Handle POST requests (adds $currentUser PersonObject to the session)
	 * 
	 * @param array $params
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');
		
		if(!is_array($params))
			throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doPOST method!');
				
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields())
				throw new SecurityException('This page cannot accept post data from remote servers!');
		
			if (isset($params['loginBut'])) {
				// if the database has not been set up yet, accept a login from the config admin username/password
				if(!AlphaDAO::isInstalled()) {
					if ($params['email'] == $config->get('sysInstallUsername') && crypt($params['password'], $config->get('sysInstallPassword')) == 
						crypt($config->get('sysInstallPassword'), $config->get('sysInstallPassword'))) {
							
						self::$logger->info('Logging in ['.$params['email'].'] at ['.date("Y-m-d H:i:s").']');
						$admin = new PersonObject();
						$admin->set('displayName', 'Admin');
						$admin->set('email', $params['email']);
						$admin->set('password', crypt($params['password'], $config->get('sysInstallPassword')));
						$admin->set('OID', '00000000001');
						$_SESSION['currentUser'] = $admin;
						if ($this->getNextJob() != '') {
							$url = FrontController::generateSecureURL('act='.$this->getNextJob());
							self::$logger->info('Redirecting to ['.$url.']');
							header('Location: '.$url);
							exit;
						}else{
							header('Location: '.$config->get('sysURL').'alpha/controller/Install.php');
							exit;
						}
					}else{
						throw new ValidationException('Failed to login user '.$params['email'].', the password is incorrect!');
					}
				}else{
					// here we are attempting to load the person from the email address
					$this->personObject->loadByAttribute('email', $params['email'], true);
					
					AlphaDAO::disconnect();
					
					// checking to see if the account has been disabled
					if (!$this->personObject->isTransient() && $this->personObject->get('state') == 'Disabled')
						throw new SecurityException('Failed to login user '.$params['email'].', that account has been disabled!');
					
					// check the password
					$this->doLoginAndRedirect($params['password']);
				}
				
				echo AlphaView::displayPageHead($this);
				
				echo $this->personView->displayLoginForm();
			}
			
			if (isset($params['resetBut'])) {				
				// here we are attempting to load the person from the email address			
				$this->personObject->loadByAttribute('email', $params['email']);
				
				AlphaDAO::disconnect();
				
				// generate a new random password
				$new_password = $this->personObject->generatePassword();
									
				// now encrypt and save the new password, then e-mail the user
				$this->personObject->set('password', crypt($new_password));				
				$this->personObject->save();
					
				$message = 'The password for your account has been reset to '.$new_password.' as you requested.  You can now login to the site using your '.
					'e-mail address and this new password as before.';
				$subject = 'Password change request';
					
				$this->personObject->sendMail($message, $subject);				
					
				echo AlphaView::displayUpdateMessage('The password for the user <strong>'.$params['email'].'</strong> has been reset, and the new password '.
					'has been sent to that e-mail address.');
				echo '<a href="'.$config->get('sysURL').'">Home Page</a>';
			}
		}catch(ValidationException $e) {
			echo AlphaView::displayPageHead($this);
			
			echo AlphaView::displayErrorMessage($e->getMessage());
			
			echo $this->personView->displayLoginForm();
											
			self::$logger->warn($e->getMessage());
		}catch(SecurityException $e) {
			echo AlphaView::displayPageHead($this);
			
			echo AlphaView::displayErrorMessage($e->getMessage());
											
			self::$logger->warn($e->getMessage());
		}catch(BONotFoundException $e) {
			echo AlphaView::displayPageHead($this);
			
			echo AlphaView::displayErrorMessage('Failed to find the user \''.$params['email'].'\'');
			
			echo $this->personView->displayLoginForm();
			
			self::$logger->warn($e->getMessage());
		}
		
		echo AlphaView::displayPageFoot($this);
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Login the user and re-direct to the defined destination
	 * 
	 * @param string $password The password supplied by the user logging in
	 * @throws ValidationException
	 * @since 1.0
	 */
	protected function doLoginAndRedirect($password) {
		self::$logger->debug('>>doLoginAndRedirect(password=['.$password.'])');
		
		global $config;
		
		if (!$this->personObject->isTransient() && $this->personObject->get('state') == 'Active') {
			if (crypt($password, $this->personObject->get('password')) == $this->personObject->get('password')) {
								
				self::$logger->info('Logging in ['.$this->personObject->get('email').'] at ['.date("Y-m-d H:i:s").']');
				
				$_SESSION['currentUser'] = $this->personObject;
				
				if ($this->getNextJob() != '') {
					self::$logger->debug('<<doLoginAndRedirect');
					$url = FrontController::generateSecureURL('act='.$this->getNextJob());
					header('Location: '.$url);
					exit;
				}else{
					self::$logger->debug('<<doLoginAndRedirect');
					header('Location: '.$config->get('sysURL'));
					exit;
				}
			}else{
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
	public function before_displayPageFoot_callback() {
		global $config;
		
		return '<p><em>Version '.$config->get('sysVersion').'</em></p>';
	}
}

// now build the new controller if this file is called directly
if ('Login.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new Login();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>