<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/view/person.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 *
 * Login controller that adds the current user object to the session
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class Login extends AlphaController implements AlphaControllerInterface {
	/**
	 * The person to be logged in
	 * 
	 * @var person_object
	 */
	private $personObject;
	
	/**
	 * The person view object
	 * 
	 * @var person
	 */
	private $personView;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * constructor to set up the object
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('Login');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		$this->personObject = new person_object();
		$this->personView = new person($this->personObject);
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
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		if(!is_array($params)) {
			throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doGET method!');
			self::$logger->debug('<<doGET');
			return;
		}
		
		echo View::displayPageHead($this);
		
		if (isset($params['reset']))
			$this->personView->display_reset_form();
		else
			$this->personView->display_login_form();	
		
		echo View::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}	
	
	/**
	 * Handle POST requests (adds $currentUser person_object to the session)
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		if(!is_array($params)) {
			throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doPOST method!');
			self::$logger->debug('<<doPOST');
			return;
		}
				
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
				return;
			}
		
			if (isset($params['loginBut'])) {
				// if the database has not been set up yet, accept a login from the config admin username/password
				if(!AlphaDAO::isInstalled()) {
					if ($params['email'] == $config->get('sysInstallUsername') && crypt($params['password'], $config->get('sysInstallPassword')) == crypt($config->get('sysInstallPassword'), $config->get('sysInstallPassword'))) {
						$admin = new person_object();
						$admin->set('displayName', 'Admin');
						$admin->set('email', $params['email']);
						$admin->set('password', crypt($params['password'], $config->get('sysInstallPassword')));
						$admin->set('OID', '00000000001');
						$_SESSION['currentUser'] = $admin;
						header('Location: '.$config->get('sysURL').'alpha/controller/Install.php');
					}else{
						throw new ValidationException('Failed to login user '.$params['email'].', the password is incorrect!');
					}
				}else{
					// here we are attempting to load the person from the email address
					$this->personObject->loadByAttribute('email', $params['email'], true);
					
					// checking to see if the account has been disabled
					if (!$this->personObject->isTransient() && $this->personObject->get('state') == 'Disabled') {
						throw new SecurityException('Failed to login user '.$params['email'].', that account has been disabled!');
					}
					
					// check the password
					if (!$this->personObject->isTransient() && $this->personObject->get('state') == 'Active') {
						if (crypt($params['password'], $this->personObject->get('password')) == $this->personObject->get('password')) {				
							self::$logger->info('Logging in ['.$this->personObject->get('email').'] at ['.date("Y-m-d H:i:s").']');
							$_SESSION['currentUser'] = $this->personObject;
							if ($this->getNextJob() != '') {
								self::$logger->debug('<<doPOST');
								header('Location: '.$this->getNextJob());
							}else{
								self::$logger->debug('<<doPOST');
								header('Location: '.$config->get('sysURL'));
							}
						}else{
							throw new ValidationException('Failed to login user '.$params['email'].', the password is incorrect!');
						}
					}
				}
				
				echo View::displayPageHead($this);
				
				$this->personView->display_login_form();
			}
			
			if (isset($params['resetBut'])) {				
				// here we are attempting to load the person from the email address			
				$this->personObject->loadByAttribute('email', $params['email']);
				
				// generate a new random password
				$new_password = $this->personObject->generatePassword();
									
				// now encrypt and save the new password, then e-mail the user
				$this->personObject->set('password', crypt($new_password));				
				$this->personObject->save();
					
				$message = 'The password for your account has been reset to '.$new_password.' as you requested.  You can now login to the site using your e-mail address and this new password as before.';
				$subject = 'Password change request';
					
				$this->personObject->sendMail($message, $subject);				
					
				echo '<p class="success">The password for the user <strong>'.$params['email'].'</strong> has been reset, and the new password has been sent to that e-mail address.</p>';
				echo '<a href="'.$config->get('sysURL').'">Home Page</a>';
			}
		}catch(ValidationException $e) {
			echo View::displayPageHead($this);
			
			echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> '.$e->getMessage().'</p>
				</div>';
			
			$this->personView->display_login_form();
											
			self::$logger->warn($e->getMessage());
		}catch(SecurityException $e) {
			echo View::displayPageHead($this);
			
			echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> '.$e->getMessage().'</p>
				</div>';
											
			self::$logger->warn($e->getMessage());
		}catch(BONotFoundException $e) {
			echo View::displayPageHead($this);
			
			echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> Failed to find the user \''.$params['email'].'\'.</p>
				</div>';
			
			$this->personView->display_login_form();
			
			self::$logger->warn($e->getMessage());
		}
		
		echo View::displayPageFoot($this);
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Displays the application version number on the login screen.
	 * 
	 * @return string
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