<?php

/**
 *
 * The main person/user class for the site
 *
 * @package alpha::model
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2013, John Collins (founder of Alpha Framework).
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
class PersonObject extends AlphaDAO {
	/**
	 * The forum display name of the person
	 *
	 * @var String
	 * @since 1.0
	 */
	protected $displayName;

	/**
	 * The email address for the person
	 *
	 * @var String
	 * @since 1.0
	 */
	protected $email;

	/**
	 * The password for the person
	 *
	 * @var String
	 * @since 1.0
	 */
	protected $password;

	/**
	 * A Relation containing all of the rights groups that this person belongs to
	 *
	 * @var Relation
	 * @since 1.0
	 */
	protected $rights;

	/**
	 * A Relation containing all of the actions carried out by this person
	 *
	 * @var Relation
	 * @since 1.2.2
	 */
	protected $actions;

	/**
	 * An array of data display labels for the class properties
	 *
	 * @var array
	 * @since 1.0
	 */
	protected $dataLabels = array("OID"=>"Member ID#",
									"displayName"=>"Display Name",
									"email"=>"E-mail Address",
									"password"=>"Password",
									"state"=>"Account state",
									"URL"=>"Your site address",
									"rights"=>"Rights Group Membership",
									"actions"=>"Actions");

	/**
	 * The name of the database table for the class
	 *
	 * @var string
	 * @since 1.0
	 */
	const TABLE_NAME = 'Person';

	/**
	 * The state of the person (account status)
	 *
	 * @var Enum
	 * @since 1.0
	 */
	protected $state;

	/**
	 * The website URL of the person
	 *
	 * @var String
	 * @since 1.0
	 */
	protected $URL;

	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * Constructor for the class that populates all of the complex types with default values
	 *
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('PersonObject');
		self::$logger->debug('>>__construct()');

		// ensure to call the parent constructor
		parent::__construct();
		$this->displayName = new String();
		$this->displayName->setRule(AlphaValidator::REQUIRED_USERNAME);
		$this->displayName->setSize(70);
		$this->displayName->setHelper('Please provide a name for display on the website (only letters, numbers, and .-_ characters are allowed!).');
		$this->email = new String();
		$this->email->setRule(AlphaValidator::REQUIRED_EMAIL);
		$this->email->setSize(70);
		$this->email->setHelper('Please provide a valid e-mail address as your username.');
		$this->password = new String();
		$this->password->setSize(70);
		$this->password->setHelper('Please provide a password for logging in.');
		$this->password->isPassword(true);
		$this->state = new Enum(array(
									'Active',
									'Disabled'));
		$this->state->setValue('Active');
		$this->URL = new String();
		$this->URL->setRule(AlphaValidator::OPTIONAL_HTTP_URL);
		$this->URL->setHelper('URLs must be in the format http://some_domain/ or left blank!');
		// add unique keys to displayName and email (which is effectively the username in Alpha)
		$this->markUnique('displayName');
		$this->markUnique('email');

		$this->rights = new Relation();
		$this->markTransient('rights');

		$this->actions = new Relation();
		$this->markTransient('actions');

		$this->setupRels();

		self::$logger->debug('<<__construct');
	}

	/**
	 * Set up the transient attributes for the rights group after it has loaded
	 *
	 * @since 1.0
	 */
	protected function after_load_callback() {
		$this->setupRels();
	}

	/**
	 * Set up the transient attributes for the site after it has loaded
	 *
	 * @since 1.0
	 */
	protected function after_loadByAttribute_callback() {
		$this->setupRels();
	}

	/**
	 * Looks up the OID for the Standard rights group, then relates the new person
	 * to that group if they are not in it already.  If that group does not exist it
	 * will be recreated!
	 *
	 * @since 1.0
	 */
	protected function after_save_callback() {
		if($this->getVersionNumber()->getValue() == 1) {
			$standardGroup = new RightsObject();

			$this->setupRels();

			if(!$this->inGroup('Standard')) {
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
	}

	/**
	 * Encrypts any fields called 'password' posted for the PersonObject
	 *
	 * @since 1.0
	 */
	protected function before_populateFromPost_callback() {
		if(isset($_POST['password']) && preg_match(AlphaValidator::REQUIRED_STRING, $_POST['password']))
			$_POST['password'] = crypt($_POST['password']);
	}

	/**
	 * Sets up the Relation definitions on this BO
	 *
	 * @since 1.0
	 */
	protected function setupRels() {
		// set up MANY-TO-MANY relation person2rights
		if(isset($this->rights)) {
			$this->rights->setRelatedClass('PersonObject', 'left');
			$this->rights->setRelatedClassDisplayField('email', 'left');
			$this->rights->setRelatedClass('RightsObject', 'right');
			$this->rights->setRelatedClassDisplayField('name', 'right');
			$this->rights->setRelationType('MANY-TO-MANY');
			$this->rights->setValue($this->getID());
		}

		if(isset($this->actions)) {
			$this->actions->setValue($this->OID);
			$this->actions->setRelatedClass('ActionLogObject');
			$this->actions->setRelatedClassField('created_by');
			$this->actions->setRelatedClassDisplayField('message');
			$this->actions->setRelationType('ONE-TO-MANY');
		}
	}

	/**
	 * Setter for displayName
	 *
	 * @param string $displayName
	 * @since 1.0
	 */
	public function setDisplayName($displayName) {
		$this->displayName->setValue($displayName);
	}

	/**
	 * Getter for displayName
	 *
	 * @return String
	 * @since 1.0
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * Checks to see if the person is in the rights group specified
	 *
	 * @param string $groupName
	 * @return bool
	 * @since 1.0
	 */
	public function inGroup($groupName) {
		if(self::$logger == null)
			self::$logger = new Logger('PersonObject');
		self::$logger->debug('>>inGroup(groupName=['.$groupName.'])');

		$group = new RightsObject();

		try {
			$group->loadByAttribute('name', $groupName);
		}catch (BONotFoundException $e) {
			self::$logger->error('Unable to load the group named ['.$groupName.']');
			self::$logger->debug('<<inGroup [false]');
			return false;
		}

		$rel = $group->getMembers();

		try {
			// load all person2rights RelationLookup objects for this person
			$lookUps = $rel->getLookup()->loadAllByAttribute('leftID', $this->getID());
			foreach($lookUps as $lookUp) {
				// the rightID (i.e. RightsObject OID) will be on the right side of the value array
				$ids = $lookUp->getValue();
				// if we have found a match, return true right away
				if($ids[1] == $group->getID()) {
					self::$logger->debug('<<inGroup [true]');
					return true;
				}
			}
		}catch (BONotFoundException $e) {
			self::$logger->debug('<<inGroup [false]');
			return false;
		}

		self::$logger->debug('<<inGroup [false]');
		return false;
	}

	/**
	 * A generic method for mailing a person
	 *
	 * @param string $message
	 * @param string $subject
	 * @since 1.0
	 * @throws MailNotSentException
	 */
	public function sendMail($message, $subject) {
		global $config;

		$body = '<html><head></head><body><p>Dear '.$this->getDisplayName().',</p>';

		$body .= $message;

		$body .= '</body></html>';

		$headers = 'MIME-Version: 1.0'."\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1'."\n";
		$headers .= "From: ".$config->get('email.reply.to')."\n";

		try {
			mail($this->get('email'), $subject, $body, $headers);
		}catch (PHPException $e) {
			throw new MailNotSentException('Error sending a mail to ['.$this->get('email').']');
		}
	}

	/**
	 * Generates a random password for the user
	 *
	 * @return string
	 * @since 1.0
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
	 * @return integer
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function getCommentCount() {
		$temp = new ArticleCommentObject();

		$sqlQuery = "SELECT COUNT(OID) AS post_count FROM ".$temp->getTableName()." WHERE created_by='".$this->OID."';";

		$result = $this->query($sqlQuery);

		if(!isset($result[0])) {
			throw new AlphaException('Failed to get the count of the comments posted for the person ['.$this->getDisplayName().'], query is ['.$sqlQuery.']');
			return 0;
		}

		$row = $result[0];

		if(isset($row['post_count']))
			return $row['post_count'];
		else
			return 0;
	}
}

?>