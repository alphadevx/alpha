<?php

require_once $config->get('sysRoot').'alpha/model/DAO.inc';
require_once $config->get('sysRoot').'alpha/model/rights_object.inc';
require_once $config->get('sysRoot').'alpha/model/article_comment_object.inc';

/**
 *
 * The main person/user class for the site
 *
 * @package alpha::model
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class person_object extends DAO {
	/**
	 * The forum display name of the person
	 *
	 * @var String
	 */
	protected $displayName;

	/**
	 * The email address for the person
	 *
	 * @var String
	 */
	protected $email;

	/**
	 * The password for the person
	 *
	 * @var String
	 */
	protected $password;

	/**
	 * The access privileage level of the person
	 *
	 * @var Enum
	 * @deprecated
	 */
	protected $accessLevel;

	/**
	 * A Relation containing all of the rights groups that this person belongs to
	 *
	 * @var Relation
	 */
	protected $rights;

	/**
	 * An array of data display labels for the class properties
	 *
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"Member ID#",		
		"displayName"=>"Forum Name",		
		"email"=>"E-mail Address",
		"password"=>"Password",		
		"accessLevel"=>"Page Access Privileage Level",
		"state"=>"Account state",
		"URL"=>"Your site address",
		"rights"=>"Rights Group Membership"
		);

	/**
	 * The name of the database table for the class
	 *
	 * @var string
	 */
	const TABLE_NAME = 'person';

	/**
	 * The state of the person (account status)
	 *
	 * @var Enum
	 */
	protected $state;

	/**
	 * The website URL of the person
	 *
	 * @var String
	 */
	protected $URL;

	/**
	 * constructor for the class that populates all of the complex types with default values
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		$this->displayName = new String();
		$this->displayName->setRule(RULE_USERNAME);
		$this->displayName->setSize(70);
		$this->displayName->setHelper('Please provide a name for display on the website (only letters, numbers, and .-_ characters are allowed!).');
		$this->email = new String();
		$this->email->setRule(RULE_EMAIL);
		$this->email->setSize(70);
		$this->email->setHelper('Please provide a valid e-mail address as your username.');
		$this->password = new String();
		$this->password->setSize(70);
		$this->password->setHelper('Please provide a password for logging in.');
		$this->password->setRule('/\w+/');
		$this->accessLevel = new Enum(array(
										'Standard',
										'Moderator',
										'Admin'));
		$this->accessLevel->setValue('Standard');
		$this->state = new Enum(array(
									'Active',
									'Disabled'));
		$this->state->setValue('Active');
		$this->URL = new String();
		$this->URL->setRule(RULE_URL_BLANK);
		$this->URL->setHelper('URLs must be in the format http://some_domain/ or left blank!');
		// add unique keys to displayName and email (which is effectively the username in Alpha)
		$this->markUnique('displayName');
		$this->markUnique('email');

		$this->rights = new Relation();
		$this->markTransient('rights');
		$this->setupRels();
	}

	/**
	 * Set up the transient attributes for the rights group after it has loaded
	 */
	protected function after_load_callback() {
		$this->setupRels();		
	}

	/**
	 * Looks up the OID for the Standard rights group, then relates the new person
	 * to that group.  If that group does not exist it will be recreated!
	 */
	protected function after_save_callback() {
		if($this->getVersionNumber()->getValue() == 1) {
			$standardGroup = new rights_object();
			
			$this->setupRels();
					
			try {
				$standardGroup->loadByAttribute('name', 'Standard');
			}catch (BONotFoundException $e) {
				$standardGroup->set('name', 'Standard');
				$standardGroup->save();
			}
					
			$lookup = $this->rights->getLookup();
			$lookup->setValue(array($this->getID(), $standardGroup->getID()));
			$lookup->save();
		}
	}
	
	/**
	 * Sets up the Relation definitions on this BO
	 */
	private function setupRels() {
		// set up MANY-TO-MANY relation person2rights
		$this->rights->setRelatedClass('person_object', 'left');
		$this->rights->setRelatedClassDisplayField('email', 'left');
		$this->rights->setRelatedClass('rights_object', 'right');
		$this->rights->setRelatedClassDisplayField('name', 'right');
		$this->rights->setRelationType('MANY-TO-MANY');
		$this->rights->setValue($this->getID());
	}

	/**
	 * Setter for displayName
	 *
	 * @param string $displayName
	 */
	public function setDisplayName($displayName) {
		$this->displayName->setValue($displayName);
	}

	/**
	 * Getter for displayName
	 *
	 * @return String
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * Setter for accessLevel value
	 *
	 * @param string $accessLevel
	 * @deprecated
	 */
	public function setAccessLevel($accessLevel) {
		$this->accessLevel->setValue($accessLevel);
	}

	/**
	 * Getter for accessLevel value
	 *
	 * @return Enum
	 * @deprecated Use inGroup() instead!
	 */
	public function getAccessLevel() {
		return $this->accessLevel;
	}

	/**
	 * Checks to see if the person is in the rights group specified
	 *
	 * @param string $groupName
	 * @return bool
	 */
	public function inGroup($groupName) {
		$group = new rights_object();
			
		try {
			$group->loadByAttribute('name', $groupName);
		}catch (BONotFoundException $e) {
			self::$logger->error('Unable to load the group named ['.$groupName.']');
			return false;
		}
			
		$rel = $group->getMembers();
		try {
			// load all person2rights RelationLookup objects for this person
			$lookUps = $rel->getLookup()->loadAllByAttribute('leftID', $this->getID());
			foreach($lookUps as $lookUp) {
				// the rightID (i.e. rights_object OID) will be on the right side of the value array
				$ids = $lookUp->getValue();
				// if we have found a match, return true right away
				if($ids[1] == $group->getID())
					return true;
			}				
		}catch (BONotFoundException $e) {
			return false;
		}
			
		return false;
	}

	/**
	 * A generic method for mailing a person
	 *
	 * @param string $message
	 * @param string $subject
	 *
	 */
	function sendMail($message, $subject) {
		global $config;

		$body = "Dear ".$this->getDisplayName()."\n\n";

		$body .= $message;

		$body .= "\n\nKind regards,\n\nAdministrator\n--\n".$config->get('sysURL');

		try {
			mail($this->get('email'), $subject, $body, "From: ".$config->get('sysReplyTo')."\r\nReply-To: ".$config->get('sysReplyTo')."\r\nX-Mailer: PHP/" . phpversion());
		}catch (PHPException $e) {
			throw new MailNotSentException('Error sending a mail to ['.$this->get('email').']');
		}
	}

	/**
	 * Generates a random password for the user
	 *
	 * @return string
	 */
	public function generatePassword() {
		$alphabet = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		// the password will be 7 random characters and 2 numbers
		$newPassword = '';
		for ($i = 0; $i < 7; $i++) {
			$newPassword.= $alphabet[rand(0,25)];
		}
		$newPassword.= rand(0,100);
		$newPassword.= rand(0,100);

		return $newPassword;
	}

	/**
	 * Method for getting a count of the amount of article comments posted by the user
	 *
	 * @return int
	 */
	public function getCommentCount() {
		$temp = new article_comment_object();

		$sql_query = "SELECT COUNT(OID) AS post_count FROM ".article_comment_object::TABLE_NAME." WHERE created_by='".$this->OID."';";

		$this->lastQuery = $sql_query;

		$result = mysql_query($sql_query);

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {
			return $row['post_count'];
		}else{
			throw new AlphaException('Failed to get the count of the comments posted for the person ['.$this->getDisplayName().'], query is ['.$this->lastQuery.']');
			return 0;
		}
	}
}

?>