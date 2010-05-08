<?php

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';
require_once $config->get('sysRoot').'alpha/model/types/Date.inc';
require_once $config->get('sysRoot').'alpha/model/types/Timestamp.inc';
require_once $config->get('sysRoot').'alpha/model/types/Double.inc';
require_once $config->get('sysRoot').'alpha/model/types/Integer.inc';
require_once $config->get('sysRoot').'alpha/model/types/String.inc';
require_once $config->get('sysRoot').'alpha/model/types/Text.inc';
require_once $config->get('sysRoot').'alpha/model/types/Enum.inc';
require_once $config->get('sysRoot').'alpha/model/types/Boolean.inc';
require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/exceptions/FailedUnitCommitException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/SecurityException.inc';
require_once $config->get('sysRoot').'alpha/util/helpers/Validator.inc';

/**
 *
 * The master controller class for the framework
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
abstract class Controller {
	/**
	 * The name of the controller
	 * 
	 * @var string
	 */
	protected $name;
	
	/**
	 * Used to set access privileages for the controller to the name of the rights group 
	 * allowed to access it.  'Public' by default.
	 * 
	 * @var string
	 */ 
	protected $visibility = 'Public';
	
	/**
	 * Optionally, a BO may be set for the default validation form handling code to load in the displayPageHead()
	 * method.  The defination of this BO class will need to be included in the child controller.
	 * 
	 * @var Object
	 */
	protected $BO = null;
	
	/**
	 * Used to determine if the controller is part of a unit of work sequence 
	 * (either empty or the name of the unit)
	 * 
	 * @var string
	 */
	protected $unitOfWork;
	
	/**
	 * Stores the start time of a unit of work transaction
	 * 
	 * @var Timestamp
	 */
	protected $unitStartTime;
	
	/**
	 * Stores the end time of a unit of work transaction
	 * 
	 * @var Timestamp
	 */
	protected $unitEndTime;
	
	/**
	 * Stores the maximum allowed time duration (in seconds) of the unit of work
	 * 
	 * @var Integer
	 */
	protected $unitMAXDuration;
	
	/**
	 * The name of the first controller that is used in this unit of work
	 * 
	 * @var string
	 */
	protected $firstJob;
	
	/**
	 * The name of the next controller that is used in this unit of work
	 * 
	 * @var string
	 */
	protected $nextJob;
	
	/**
	 * The name of the previous controller that is used in this unit of work
	 * 
	 * @var string
	 */
	protected $previousJob;
	
	/**
	 * The name of the last controller that is used in this unit of work
	 * 
	 * @var string
	 */
	protected $lastJob;
	
	/**
	 * An array for storing dirty objects in a session (i.e. persistent business 
	 * objects that have not been commited to the database yet)
	 * 
	 * @var array
	 */
	protected $dirtyObjects = array();
	
	/**
	 * An array for storing new objects in a session (transient business objects that 
	 * have no OID yet)
	 * 
	 * @var array
	 */
	protected $newObjects = array();
	
	/**
	 * The title to be displayed on the controller page
	 * 
	 * @var string
	 */
	protected $title;
	
	/**
	 * Meta keywords for the controller page, generally populated from tags
	 * 
	 * @var string
	 */
	protected $keywords;
	
	/**
	 * Meta description for the controller page
	 * 
	 * @var string
	 */
	protected $description;
	
	/**
	 * Used to set status update messages to display to the user (messages stored between requests
	 * in _SESSION)
	 *
	 * @var string
	 */
	protected $statusMessage;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * Constructor for the Controller that starts a new session if required, and handles 
	 * the population of new/dirty objects from the session when available.  Accepts the name 
	 * of the rights group that has access to this controller, 'Public' by default.
	 * 
	 * @param string $visibility
	 */
	public function __construct($visibility='Public') {
		if(self::$logger == null)
			self::$logger = new Logger('Controller');
		self::$logger->debug('>>__construct(visibility=['.$visibility.'])');
		
	 	global $config;
	 	
	 	// kick off new session, or reuse existing one
	 	if(!isset($_SESSION))
	 		session_start();
	 	
	 	// set the access rights to the group name indicated
	 	$this->visibility = $visibility;
	 	
	 	// check the current user's rights on access to the page controller
	 	if(!$this->checkRights())
	 		$this->accessError();
	 	
	 	// if configured to do so, force redirect to the front controller
	 	if($config->get('sysForceFC') && basename($_SERVER['PHP_SELF']) != 'FC.php' && basename($_SERVER['PHP_SELF']) != 'index.php') {
	 		// set the correct HTTP header for the response
    		header('HTTP/1.1 301 Moved Permanently');
    		
    		if(empty($_SERVER['QUERY_STRING']))
    			header('Location: '.$config->get('sysURL').'/FC.php?act='.get_class($this));
    		else
    			header('Location: '.$config->get('sysURL').'/FC.php?act='.get_class($this).'&'.$_SERVER['QUERY_STRING']);
 
		    // we're done here
		    self::$logger->debug('<<__construct');
    		return;
	 	}
	 	
	 	$this->unitStartTime = new Timestamp();
	 	$this->unitEndTime = new Timestamp();
	 	$this->unitMAXDuration = new Integer();
	 	
	 	if($this->name == '')
	 		$this->setName('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	 	
	 	if(isset($_SESSION['unitOfWork'])) {	 		
	 		$this->setUnitOfWork($_SESSION['unitOfWork']);
	 	}
	 	
	 	if(isset($_SESSION['dirtyObjects'])) {
	 		$this->dirtyObjects = $_SESSION['dirtyObjects'];
	 	}
	 	
	 	if(isset($_SESSION['newObjects'])) {	 		
	 		$this->newObjects = $_SESSION['newObjects'];
	 	}
	 	
	 	if(isset($_SESSION['statusMessage'])) {
	 		$this->setStatusMessage($_SESSION['statusMessage']);
	 	}

	 	self::$logger->debug('<<__construct');
	 }
	 
	 /**
	  * Get the BO for this controller (if any)
	  * 
	  * @return mixed
	  */
	 public function getBO() {
	 	self::$logger->debug('>>getBO()');
	 	self::$logger->debug('<<getBO ['.print_r($this->BO, true).']');
	 	return $this->BO;
	 }
	 
	 /**
	  * Setter for the BO for this controller
	  * 
	  * @param Object $BO
	  */ 
	 public function setBO($BO) {
	 	self::$logger->debug('>>setBO(BO=['.print_r($BO, true).'])');
	 	$this->BO = $BO;
	 	self::$logger->debug('<<setBO');
	 }
	 
	 /**
	  * Get the name of the unit of work job
	  * 
	  * @return string
	  */
	 public function getName() {
	 	self::$logger->debug('>>getName()');
	 	self::$logger->debug('<<getName ['.$this->name.']');
	 	return $this->name;
	 }
	 
	 /**
	  * Setter for the unit of work job name
	  * 
	  * @param string $name
	  */ 
	 public function setName($name) {			
	 	self::$logger->info('>>setName(name=['.$name.'])');
	 	$this->name = $name;
	 	self::$logger->debug('<<setName');
	 }
	 
	 /**
	  * Get the name of the rights group that has access to this controller
	  * 
	  * @return string $visibility
	  */
	 public function getVisibility() {
	 	self::$logger->debug('>>getVisibility()');
	 	self::$logger->debug('<<getVisibility ['.$this->visibility.']');
	 	return $this->visibility;
	 }
	 
	 /**
	  * Setter for the name of the rights group that has access to this controller
	  * 
	  * @param string $visibility
	  */
	 public function setVisibility($visibility) {
	 	self::$logger->debug('>>setVisibility(visibility=['.$visibility.'])');
	 	$this->visibility = $visibility;
	 	self::$logger->debug('<<setVisibility');
	 }
	 
	 /**
	  * Gets the name of the first job in this unit of work
	  * 
	  * @return string
	  */
	 public function getFirstJob() {
	 	self::$logger->debug('>>getFirstJob()');
	 	self::$logger->debug('<<getFirstJob ['.$this->firstJob.']');
	 	return $this->firstJob;
	 }
	 
	 /**
	  * Gets the name of the next job in this unit of work
	  * 
	  * @return string
	  */
	 public function getNextJob() {
	 	self::$logger->debug('>>getNextJob()');
	 	self::$logger->info('<<getNextJob ['.$this->nextJob.']');
	 	return $this->nextJob;
	 }
	 
	 /**
	  * Gets the name of the previous job in this unit of work
	  * 
	  * @return string
	  */
	 public function getPreviousJob() {
	 	self::$logger->debug('>>getPreviousJob()');
	 	self::$logger->debug('<<getPreviousJob ['.$this->previousJob.']');
	 	return $this->previousJob;
	 }
	 
	 /**
	  * Gets the name of the last job in this unit of work
	  * 
	  * @return string
	  */
	 public function getLastJob() {
	 	self::$logger->debug('>>getLastJob()');
	 	self::$logger->debug('<<getLastJob ['.$this->lastJob.']');
	 	return $this->lastJob;
	 }
	 
	 /**
	  * Sets the name of the controller job sequence to the values in the supplied 
	  * array (and stores the array in the session)
	  * 
	  * @param array $jobs
	  */
	 public function setUnitOfWork($jobs) {
	 	self::$logger->info('>>setUnitOfWork(jobs=['.print_r($jobs, true).'])');
	 	
	 	if(method_exists($this, 'before_setUnitOfWork_callback'))
			$this->before_setUnitOfWork_callback();
			
	 	if(!is_array($jobs)) {
			throw new IllegalArguementException('Bad $jobs array ['.var_export($jobs, true).'] passed to setUnitOfWork method!');
			self::$logger->debug('<<setUnitOfWork');
			return;
		}
			
		// clear out any previous unit of work from the session
		$_SESSION['unitOfWork'] = null;		
		$this->dirtyObjects = array();
		$this->newObjects = array();
		
	 	$numOfJobs = count($jobs);
	 	
	 	for($i=0; $i<$numOfJobs; $i++) {
	 		// the first job in the sequence
	 		if($i==0) {
	 			$this->firstJob = $jobs[$i];
	 			self::$logger->info('First job ['.$this->firstJob.']');
	 		}
	 		// found the current job
	 		if($this->name == $jobs[$i]) {
	 			if(isset($jobs[$i-1])) {
	 				// set the previous job if it exists
	 				$this->previousJob = $jobs[$i-1];
	 				self::$logger->info('Previous job ['.$this->previousJob.']');
	 			}
	 			if(isset($jobs[$i+1])) {
	 				// set the next job if it exists
	 				$this->nextJob = $jobs[$i+1];
	 				self::$logger->info('Next job ['.$this->nextJob.']');
	 			}
	 		}
	 		// the last job in the sequence
	 		if($i==($numOfJobs-1)) {
	 			$this->lastJob = $jobs[$i];
	 		}	
	 	}
	 	$_SESSION['unitOfWork'] = $jobs;
	 	
	 	if(method_exists($this, 'after_setUnitOfWork_callback'))
			$this->after_setUnitOfWork_callback();
			
		self::$logger->debug('<<setUnitOfWork');
	 }
	 
	 /**
	  * Getter for the unit start time
	  * 
	  * @return Timestamp $unitStartTime
	  */
	 public function getStartTime() {
	 	self::$logger->debug('>>getStartTime()');
	 	self::$logger->debug('<<getStartTime ['.$this->unitStartTime.']');
	 	return $this->unitStartTime;
	 }
	 
	 /**
	  * Setter for the unit start time (value will be stored in the session as key unitStartTime)
	  * 
	  * @param int $year
	  * @param int $month
	  * @param int $day
	  * @param int $hour
	  * @param int $minute
	  * @param int $second
	  */
	 public function setUnitStartTime($year, $month, $day, $hour, $minute, $second) {
	 	self::$logger->debug('>>setUnitStartTime(year=['.$year.'], month=['.$month.'], day=['.$day.'], hour=['.$hour.'], minute=['.$minute.'], second=['.$second.'])');
	 	$this->unitStartTime->setTimestampValue($year, $month, $day, $hour, $minute, $second);
	 	$_SESSION['unitStartTime'] = $this->unitStartTime->getValue();
	 	self::$logger->debug('<<setUnitStartTime');
	 }
	 
	 /**
	  * Getter for the unit end time
	  * 
	  * @return Timestamp $unitEndTime
	  */
	 public function getEndTime() {
	 	self::$logger->debug('>>getEndTime()');
	 	self::$logger->debug('<<getEndTime ['.$this->unitEndTime.']');
	 	return $this->unitEndTime;
	 }
	 
	 /**
	  * Setter for the unit end time (value will be stored in the session as key unitEndTime)
	  * 
	  * @param int $year
	  * @param int $month
	  * @param int $day
	  * @param int $hour
	  * @param int $minute
	  * @param int $second
	  */
	 public function setUnitEndTime($year, $month, $day, $hour, $minute, $second) {
	 	self::$logger->debug('>>setUnitEndTime(year=['.$year.'], month=['.$month.'], day=['.$day.'], hour=['.$hour.'], minute=['.$minute.'], second=['.$second.'])');
	 	$this->unitEndTime->setTimestampValue($year, $month, $day, $hour, $minute, $second);
	 	$_SESSION['unitEndTime'] = $this->unitEndTime->getValue();
	 	self::$logger->debug('<<setUnitEndTime');
	 }
	 
	 /**
	  * Getter for the unit of work MAX duration
	  * 
	  * @return Integer $unitMAXDuration
	  */
	 public function getMAXDuration() {
	 	self::$logger->debug('>>getMAXDuration()');
	 	self::$logger->debug('<<getMAXDuration ['.$this->unitMAXDuration.']');
	 	return $this->unitMAXDuration;
	 }
	 
	 /**
	  * Setter for the unit MAX duration
	  * 
	  * @param integer $unitMAXDuration
	  */
	 public function setUnitMAXDuration($duration) {
	 	self::$logger->debug('>>setUnitMAXDuration(duration=['.$duration.'])');
	 	$this->unitMAXDuration->setValue($duration);
	 	self::$logger->debug('<<setUnitMAXDuration');
	 }
	 
	 /**
	  * Calculates and returns the unit of work current duration in seconds
	  * 
	  * @return integer $duration
	  */
	 public function getUnitDuration() {
	 	self::$logger->debug('>>getUnitDuration()');
	 	
	 	$intStartTime = mktime(
	 		$this->unitStartTime->getHour(),
	 		$this->unitStartTime->getMinute(),
	 		$this->unitStartTime->getSecond(),
	 		$this->unitStartTime->getMonth(),
	 		$this->unitStartTime->getDate(),
	 		$this->unitStartTime->getYear()
	 		);
	 	
	 	$intEndTime = mktime(
	 		$this->unitEndTime->getHour(),
	 		$this->unitEndTime->getMinute(),
	 		$this->unitEndTime->getSecond(),
	 		$this->unitEndTime->getMonth(),
	 		$this->unitEndTime->getDate(),
	 		$this->unitEndTime->getYear()
	 		);
	 	
	 	self::$logger->debug('<<getUnitDuration ['.$intEndTime-$intStartTime.']');
	 	return $intEndTime-$intStartTime;
	 }
	 
	 /**
	  * Adds the supplied business object to the dirtyObjects array in the session
	  * 
	  * @param Object $object
	  */
	 public function markDirty($object) {
	 	self::$logger->debug('>>markDirty(object=['.print_r($object, true).'])');
	 	
	 	if(method_exists($this, 'before_markDirty_callback'))
			$this->before_markDirty_callback();
		
	 	$this->dirtyObjects[count($this->dirtyObjects)] = $object;
	 	
	 	$_SESSION['dirtyObjects'] = $this->dirtyObjects;
	 	
	 	if(method_exists($this, 'after_markDirty_callback'))
			$this->after_markDirty_callback();
		
		self::$logger->debug('<<markDirty');
	 }
	 
	 /**
	  * Getter for the dirty objects array
	  * 
	  * @return array $dirtyObjects
	  */
	 public function getDirtyObjects() {
	 	self::$logger->debug('>>getDirtyObjects()');
	 	self::$logger->debug('<<getDirtyObjects ['.print_r($this->dirtyObjects, true).']');
	 	return $this->dirtyObjects;
	 }
	 
	 /**
	  * Adds a newly created business object to the newObjects array in the session
	  * 
	  * @param Object $object
	  */
	 public function markNew($object) {
	 	self::$logger->debug('>>markNew(object=['.print_r($object, true).'])');
	 	
	 	if(method_exists($this, 'before_markNew_callback'))
			$this->before_markNew_callback();
		
	 	$this->newObjects[count($this->newObjects)] = $object;
	 	
	 	$_SESSION['newObjects'] = $this->newObjects;
	 	
	 	if(method_exists($this, 'after_markNew_callback'))
			$this->after_markNew_callback();
			
		self::$logger->debug('<<markNew');
	 }
	 
	 /**
	  * Getter for the new objects array
	  * 
	  * @return array $newObjects
	  */
	 public function getNewObjects() {
	 	self::$logger->debug('>>getNewObjects()');
	 	self::$logger->debug('<<getNewObjects ['.print_r($this->newObjects, true).']');
	 	return $this->newObjects;
	 }
	 
	/**
	 * Commits (saves) all of the new and modified (dirty) objects in the unit of work to the database
	 */
	public function commit() {
		self::$logger->debug('>>commit()');
		
		if(method_exists($this, 'before_commit_callback'))
			$this->before_commit_callback();
		
		$sqlQuery = 'BEGIN;';

		$result = mysql_query($sqlQuery);		

		$newObjects = $this->getNewObjects();

		for ($i = 0; $i < count($newObjects); $i++) {
			try {
				$newObjects[$i]->save();
			}catch (FailedSaveException $e) {
				throw new FailedUnitCommitException($e->getMessage());
				self::$logger->error('Failed to save new object of type ['.get_class($newObjects[$i]).'], aborting...');
				$this->abort();
				return;
			}catch (LockingException $e) {
				throw new FailedUnitCommitException($e->getMessage());
				self::$logger->error('Failed to save new object of type ['.get_class($newObjects[$i]).'], aborting...');
				$this->abort();
				return;
			}
		}
		
		$dirtyObjects = $this->getDirtyObjects();

		for ($i = 0; $i < count($dirtyObjects); $i++) {
			try {
				$dirtyObjects[$i]->save();
			}catch (FailedSaveException $e) {
				throw new FailedUnitCommitException($e->getMessage());
				self::$logger->error('Failed to save OID ['.$dirtyObjects[$i]->getID().'] of type ['.get_class($dirtyObjects[$i]).'], aborting...');
				$this->abort();
				return;
			}catch (LockingException $e) {
				throw new FailedUnitCommitException($e->getMessage());
				self::$logger->error('Failed to save OID ['.$dirtyObjects[$i]->getID().'] of type ['.get_class($dirtyObjects[$i]).'], aborting...');
				$this->abort();
				return;
			}
		}
		
		$sqlQuery = 'COMMIT;';		

		$result = mysql_query($sqlQuery);
		
		$_SESSION['unitOfWork'] = null;
		$_SESSION['dirtyObjects'] = null;
		$_SESSION['newObjects'] = null;
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_commit_callback'))
				$this->after_commit_callback();
			
			self::$logger->debug('<<commit');
		}else{					
			throw new FailedUnitCommitException('Failed to commit the transaction, MySQL error is ['.mysql_error().']');
			self::$logger->debug('<<commit');
		}		
	}
	
	/**
	 * Method to clearup a cancelled unit of work
	 */
	public function abort() {
		self::$logger->debug('>>abort()');
		
		if(method_exists($this, 'before_abort_callback'))
			$this->before_abort_callback();
		
		$_SESSION['unitOfWork'] = null;
		$_SESSION['dirtyObjects'] = null;
		$_SESSION['newObjects'] = null;
		
		$sqlQuery = 'ROLLBACK;';		

		$result = mysql_query($sqlQuery);
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_abort_callback'))
				$this->after_abort_callback();
			
			self::$logger->debug('<<abort');
		}else{
			throw new AlphaException('Failed to rollback the transaction, MySQL error is ['.mysql_error().']');
			self::$logger->debug('<<abort');
		}		
	}
	
	/**
	 * Getter for the page title
	 * 
	 * @return string
	 */
	public function getTitle() {
		self::$logger->debug('>>getTitle()');
		self::$logger->debug('<<getTitle ['.$this->title.']');
		return $this->title;	
	}
	
	/**
	 * Setter for the page title
	 * 
	 * @param string $title
	 */
	public function setTitle($title) {
		self::$logger->debug('>>setTitle(title=['.$title.'])');
		self::$logger->debug('<<setTitle');
		$this->title = $title;	
	}
	
	/**
	 * Getter for the page description
	 * 
	 * @return string
	 */
	public function getDescription() {
		self::$logger->debug('>>getDescription()');
		self::$logger->debug('<<getDescription ['.$this->description.']');
		return $this->description;	
	}
	
	/**
	 * Setter for the page description
	 * 
	 * @param string $description
	 */
	public function setDescription($description) {
		self::$logger->debug('>>setDescription(description=['.$description.'])');
		self::$logger->debug('<<setDescription');
		$this->description = $description;	
	}
	
	/**
	 * Getter for the page keywords
	 * 
	 * @return string page keywords
	 */
	public function getKeywords() {
		self::$logger->debug('>>getKeywords()');
		self::$logger->debug('<<getKeywords ['.$this->keywords.']');
		return $this->keywords;	
	}
	
	/**
	 * Setter for the page keywords, should pass a comma-seperated list as a string
	 * 
	 * @param string $keywords
	 */
	public function setKeywords($keywords) {
		self::$logger->debug('>>setKeywords(keywords=['.$keywords.'])');
		self::$logger->debug('<<setKeywords');
		$this->keywords = $keywords;	
	}
	
	/**
	 * Method to display an access error for trespassing users
	 */
	public function accessError() {
		self::$logger->debug('>>accessError()');
		
		if(method_exists($this, 'before_accessError_callback'))
			$this->before_accessError_callback();
		
		global $config;		
		
		if(isset($_SESSION['currentUser']))
			self::$logger->warn('The user ['.$_SESSION['currentUser']->get('email').'] attempted to access the resource ['.$_SERVER['REQUEST_URI'].'] but was denied due to insufficient rights');
		else
			self::$logger->warn('An unknown user attempted to access the resource ['.$_SERVER['REQUEST_URI'].'] but was denied due to insufficient rights');
			
		header('HTTP/1.1 403 Forbidden');
		$front = new FrontController();
		echo $front->renderErrorPage(403, 'You do not have the correct access rights to view this page.  If you have not logged in yet, try going back to the home page and logging in from there.');
		
		if(method_exists($this, 'after_accessError_callback'))
			$this->after_accessError_callback();
		
		self::$logger->debug('<<accessError');
		// no further execution should take place
		exit;
	}
	
	/**
	 * Checks the user rights of the currently logged-in person against the page 
	 * visibility set for this controller.  Will invoke accessError() if the user has
	 * not got the correct rights.
	 * 
	 * @return boolean
	 */
	public function checkRights() {
		self::$logger->debug('>>checkRights()');
		
		if(method_exists($this, 'before_checkRights_callback'))
			$this->before_checkRights_callback();
		
		// firstly if the page is Public then there is no issue
		if ($this->getVisibility() == 'Public') {
			if(method_exists($this, 'after_checkRights_callback'))
				$this->after_checkRights_callback();
			
			self::$logger->debug('<<checkRights [true]');
			return true;
		}else{
			// the person is logged in?
			if (isset($_SESSION['currentUser'])) {
				// checking for admins (can access everything)				
				if ($_SESSION['currentUser']->inGroup('Admin')) {
					if(method_exists($this, 'after_checkRights_callback'))
						$this->after_checkRights_callback();
					
					self::$logger->debug('<<checkRights [true]');
					return true;
				} elseif ($_SESSION['currentUser']->inGroup($this->getVisibility())) {
					if(method_exists($this, 'after_checkRights_callback'))
						$this->after_checkRights_callback();
					
					self::$logger->debug('<<checkRights [true]');
					return true;
				// the person is editing their own profile which is allowed
				} elseif (get_class($this->BO) == 'person_object' && $_SESSION['currentUser']->getDisplayName() == $this->BO->getDisplayName()) {
					if(method_exists($this, 'after_checkRights_callback'))
						$this->after_checkRights_callback();
					
					self::$logger->debug('<<checkRights [true]');
					return true;
				}else{
					$this->accessError();
					self::$logger->debug('<<checkRights [false]');
					return false;
				}
			}else{ // the person is NOT logged in
				$this->accessError();
				self::$logger->debug('<<checkRights [false]');
				return false;
			}
		}
	}
	
	/**
	 * Method to check the validity of the two hidden form security
	 * fields which aim to ensure that a post to the controller is being sent from 
	 * the same server that is hosting it.
	 * 
	 * @return boolean
	 */
	public static function checkSecurityFields() {
		if(self::$logger == null)
			self::$logger = new Logger('Controller');
		self::$logger->debug('>>checkSecurityFields()');
		
		// the server hostname + today's date
		$var1 = md5($_SERVER['HTTP_HOST'].date("Ymd"));
		// the server's IP plus $var1
		$var2 = md5($_SERVER['REMOTE_ADDR'].$var1);
		
		if(empty($_REQUEST['var1']) || empty($_REQUEST['var2'])) {
			self::$logger->debug('<<checkSecurityFields [false]');
			return false;
		}
		
		if ($var1 == $_REQUEST['var1'] && $var2 == $_REQUEST['var2']) {			
			self::$logger->debug('<<checkSecurityFields [true]');
			return true;
		}else{
			/*
			 * Here we are implementing a "grace period" of one hour if the time is < 1:00AM, we will accept
			 * a match for yesterday's date in the security fields
			 * 
			 */			
			
			// the server hostname + today's date less 1 hour (i.e. yesterday where time is < 1:00AM)
			$var1 = md5($_SERVER['HTTP_HOST'].date("Ymd", (time()-3600)));
			// the server's IP plus $var1
			$var2 = md5($_SERVER['REMOTE_ADDR'].$var1);
			
			if ($var1 == $_REQUEST['var1'] && $var2 == $_REQUEST['var2']) {				
				self::$logger->debug('<<checkSecurityFields [true]');
				return true;
			}else{
				self::$logger->debug('<<checkSecurityFields [false]');
				return false;
			}
		}
	}
	
	/**
	 * Returns the name of a custom controller if one is found, otherwise returns null
	 *
	 * @param string $BOName The classname of the business object
	 * @param string $mode The mode of the controller (create, view, edit)
	 * @return string
	 */
	public static function getCustomControllerName($BOName, $mode) {
		if(self::$logger == null)
			self::$logger = new Logger('Controller');
		self::$logger->debug('>>getCustomControllerName(BOName=['.$BOName.'], mode=['.$mode.'])');
		
		global $config;
		
		// strip the _object part from the class name
		$BOName = substr($BOName, 0, strpos($BOName, '_object'));
		// uppercase the first letter of each word, e.g. create cart becomes Create Cart
		$controllerName = ucwords($mode.' '.$BOName);
		// remove spaces
		$controllerName = str_replace(' ', '', $controllerName);

		self::$logger->debug('Custom controller name is ['.$controllerName.']');
		
		if (file_exists($config->get('sysRoot').'controller/'.$controllerName.'.php')) {
			self::$logger->debug('<<getCustomControllerName');
			return $controllerName;
		}else{
			self::$logger->debug('<<getCustomControllerName');
			return null;
		}		
	}
	
	/**
	 * Does a HTTP redirect to a custom controller if one is found
	 *
	 * @param string $BOName The classname of the business object
	 * @param string $mode The mode of the controller (create, view, edit)
	 */
	protected function loadCustomController($BOName, $mode) {
		self::$logger->debug('>>loadCustomController(BOName=['.$BOName.'], mode=['.$mode.'])');
		
		global $config;
		
		// strip the _object part from the class name
		$BOName = substr($BOName, 0, strpos($BOName, '_object'));
		// uppercase the first letter of each word, e.g. create cart becomes Create Cart
		$controllerName = ucwords($mode.' '.$BOName);
		// remove spaces
		$controllerName = str_replace(' ', '', $controllerName);

		self::$logger->debug('Custom controller name is ['.$controllerName.']');
		
		// just making sure that we are not already using the custom controller
		if(get_class($this) != $controllerName) {
			if (file_exists($config->get('sysRoot').'controller/'.$controllerName.'.php')) {
				self::$logger->debug('Custom controller found, redirecting...');
				// handle secure URLs
				if(isset($_GET['tk'])) {
					$params = FrontController::decodeQueryParams($_GET['tk']);
					header('Location: '.FrontController::generateSecureURL('act='.$controllerName.'&'.$params));
				}else{
					header('Location: '.$config->get('sysURL').'controller/'.$controllerName.'.php?'.$_SERVER['QUERY_STRING']);
				}
			}
	
			if (file_exists($config->get('sysRoot').'alpha/controller/'.$controllerName.'.php')) {
				self::$logger->debug('Custom controller found, redirecting...');
				// handle secure URLs
				if(isset($_GET['tk'])) {
					$params = FrontController::decodeQueryParams($_GET['tk']);
					header('Location: '.FrontController::generateSecureURL('act='.$controllerName.'&'.$params));
				}else{
					header('Location: '.$config->get('sysURL').'alpha/controller/'.$controllerName.'.php?'.$_SERVER['QUERY_STRING']);
				}	
			}
		}
		
		self::$logger->debug('<<loadCustomController');
	}
	
	/**
	 * Set the status message in the _SESSION to the value provided.
	 * 
	 * @param $message
	 */
	public function setStatusMessage($message) {
		$this->statusMessage = $message;
		$_SESSION['statusMessage'] = $message;
	}
	
	/**
	 * Gets the current status message for this controller.  Note that by getting the current
	 * status message, you clear out the value stored in _SESSION so this method can only be used
	 * to get the status message once for display purposes.
	 * 
	 * @return string
	 */
	public function getStatusMessage() {
		$_SESSION['statusMessage'] = null;
		return $this->statusMessage;
	}
}

?>