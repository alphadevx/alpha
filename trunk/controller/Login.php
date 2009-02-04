<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/view/person.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
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
class Login extends Controller implements AlphaControllerInterface {
	/**
	 * The person to be logged in
	 * 
	 * @var person_object
	 */
	private $personObject;
	
	/**
	 * The person view object
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
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		$this->setName(FrontController::encodeQuery('act=login'));
		
		$this->personObject = new person_object();
		$this->personView = new person($this->personObject);
		$this->setBO($this->personObject);
		
		// set up the title and meta details
		$this->setTitle('Login to the site');
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
		
		echo View::displayPageHead($this);
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
				return;
			}
		
			if (isset($params['loginBut'])) {
				// here we are attempting to load the person from the email address
				$this->personObject->loadByAttribute('email', $params['email']);
				
				// checking to see if the account has been disabled
				if (!$this->personObject->isTransient() && $this->personObject->get('state') == 'Disabled') {
					throw new SecurityException('Failed to login user '.$params['email'].', that account has been disabled!');	
					self::$logger->debug('<<doPOST');
					return;
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
						self::$logger->debug('<<doPOST');
						return;
					}
				}
				
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
			echo '<p class="error"><br>'.$e->getMessage().'</p>';
			
			$this->personView->display_login_form();
											
			self::$logger->warn($e->getMessage());
		}catch(SecurityException $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';								
			self::$logger->warn($e->getMessage());
		}catch(BONotFoundException $e) {
			echo '<p class="error"><br>Failed to find the user \''.$params['email'].'\'</p>';

			$this->personView->display_login_form();
			
			self::$logger->warn($e->getMessage());
		}
		
		echo View::displayPageFoot($this);
		self::$logger->debug('<<doPOST');
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