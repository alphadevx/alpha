<?php

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/model/types/Date.inc';
require_once $config->get('sysRoot').'alpha/model/types/Timestamp.inc';
require_once $config->get('sysRoot').'alpha/model/types/Double.inc';
require_once $config->get('sysRoot').'alpha/model/types/Integer.inc';
require_once $config->get('sysRoot').'alpha/model/types/String.inc';
require_once $config->get('sysRoot').'alpha/model/types/Text.inc';
require_once $config->get('sysRoot').'alpha/model/types/Enum.inc';
require_once $config->get('sysRoot').'alpha/model/types/Boolean.inc';
require_once $config->get('sysRoot').'alpha/view/widgets/calendar.php';
require_once $config->get('sysRoot').'alpha/view/widgets/record_selector.php';

/**
 * The master rendering view class for the framework
 * 
 * @package alpha::view
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class View {
	/**
	 * The business object that will be rendered
	 * 
	 * @var DAO
	 */
	protected $BO;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;

	/**
	 * Constructor for the View
	 * 
	 * @param DAO $BO
	 */
	public function __construct($BO) {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>__construct(BO=['.print_r($BO, true).'])');
				
		$this->BO = $BO;
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Static method which returns a View object or a custom child view for the BO specified
	 * if one exists
	 * 
	 * @param object $BO the main business object that this view is going to render
	 * @param bool $return_parent flag to enforce the return of this object instead of a child (defaults to false)
	 * @return object returns a View object, or a child view object from the /view directory if one exists for this BO 
	 */
	public static function getInstance($BO, $returnParent=false) {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>getInstance(BO=['.print_r($BO, true).'], returnParent=['.$returnParent.'])');
		
		global $config;
		
		$filename = get_class($BO);
		// remove the _object part
		$filename = str_replace('_object', '', $filename);
		// replace _ with space, then uppercase words
		$filename = str_replace('_', ' ', $filename);
		$filename = ucwords($filename).'View';
		// finally, remove spaces
		$filename = str_replace(' ', '', $filename);		

		// Check to see if a custom view exists for this BO, and if it does return that view instead		
		if (!$returnParent) {
			if (file_exists($config->get('sysRoot').'view/'.$filename.'.inc')) {
				require_once $config->get('sysRoot').'view/'.$filename.'.inc';

				self::$logger->debug('<<getInstance [new '.$filename.'('.get_class($BO).')]');
				return new $filename($BO);
			}elseif (file_exists($config->get('sysRoot').'alpha/view/'.$filename.'.inc')) {
				require_once $config->get('sysRoot').'alpha/view/'.$filename.'.inc';

				self::$logger->debug('<<getInstance [new '.$filename.'('.get_class($BO).')]');
				return new $filename($BO);
			}else{
				self::$logger->debug('<<getInstance [new View('.get_class($BO).', true)]');
				return new View($BO, true);
			}
		}else{
			self::$logger->debug('<<getInstance [new View('.get_class($BO).', true)]');
			return new View($BO, true);
		}
	}

	/**
	 * Simple setter for the view business object
	 * 
	 * @param object $BO
	 */
	public function setBO($BO) {		
		self::$logger->debug('>>setBO(BO=['.print_r($BO, true).'])');
		
		$this->BO = $BO;
		
		self::$logger->debug('<<setBO');
	}

	/**
	 * Renders the default create view
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function createView($fields=array()) {		
		self::$logger->debug('>>createView(fields=['.print_r($fields, true).'])');
		
		if(method_exists($this, 'before_createView_callback'))
			$this->before_createView_callback();
		
		global $config;
		
		// the form action
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// the form ID
		$fields['formID'] = get_class($this->BO).'_'.$this->BO->getID();
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('create');
		
		// buffer HTML output for Create and Cancel buttons		
		$button = new button('submit', 'Create', 'createBut');
		$fields['createButton'] = $button->render();
		
		$button = new button("document.location.replace('".FrontController::generateSecureURL('act=ListBusinessObjects')."')", 'Cancel', 'cancelBut');
		$fields['cancelButton'] = $button->render();		
		
		// buffer security fields to $formSecurityFields variable		
		$fields['formSecurityFields'] = $this->renderSecurityFields();		

		$this->loadTemplate($this->BO, 'create', $fields);
		
		if(method_exists($this, 'after_createView_callback'))
			$this->after_createView_callback();
			
		self::$logger->debug('<<createView');
	}

	/**
	 * Renders a form to enable object editing
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function editView($fields=array()) {
		self::$logger->debug('>>editView(fields=['.print_r($fields, true).'])');
		
		if(method_exists($this, 'before_editView_callback'))
			$this->before_editView_callback();
		
		global $config;

		// the form action
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// the form ID
		$fields['formID'] = get_class($this->BO).'_'.$this->BO->getID();
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('edit');
		
		// buffer HTML output for Create and Cancel buttons		
		$button = new button('submit', 'Save', 'saveBut');
		$fields['saveButton'] = $button->render();
		
		$button = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.getElementById('delete_form').submit();}", "Delete", "deleteBut");
		$fields['deleteButton'] = $button->render();
		
		$button = new button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'", "Back to List", "cancelBut");
		$fields['cancelButton'] = $button->render();		
				
		// buffer security fields to $formSecurityFields variable		
		$fields['formSecurityFields'] = $this->renderSecurityFields();

		// OID will need to be posted for optimistic lock checking
		$fields['version_num'] = $this->BO->getVersionNumber();

		$this->loadTemplate($this->BO, 'edit', $fields);
		
		if(method_exists($this, 'after_editView_callback'))
			$this->after_editView_callback();
			
		self::$logger->debug('<<editView');
	}

	/**
	 * Renders the list view
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function listView($fields=array()) {
		self::$logger->debug('>>listView(fields=['.print_r($fields, true).'])');
		
		if(method_exists($this, 'before_listView_callback'))
			$this->before_listView_callback();
		
		global $config;
		
		// the form action
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// work out how many columns will be in the table
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = array_keys($reflection->getDefaultProperties());		
		$fields['colCount'] = 1+count(array_diff($properties, $this->BO->getDefaultAttributes(), $this->BO->getTransientAttributes()));
		
		// get the class attributes
		$properties = $reflection->getProperties();
		
		$html = '';
		
		$html .= '<tr>';
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			// skip over password fields
			$property = $this->BO->getPropObject($propName);
			if(!($property instanceof String && $property->checkIsPassword())) {			
				if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
					$html .= '	<th>'.$this->BO->getDataLabel($propName).'</th>';				
				}
				if ($propName == 'OID')
					$html .= '	<th>'.$this->BO->getDataLabel($propName).'</th>';
			}else{
				$fields['colCount'] = $fields['colCount']-1;
			}
		}
		$html .= '</tr><tr>';
		
		$fields['formHeadings'] = $html;
		
		$html = '';

		// and now the values
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			$property = $this->BO->getPropObject($propName);
			if(!($property instanceof String && $property->checkIsPassword())) {
				if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
					$propClass = get_class($this->BO->getPropObject($propName));
					
					if ($propClass == 'Text') {
						$text = htmlentities($this->BO->get($propName));
						if(strlen($text) > 70)
							$html .= '	<td>&nbsp;'.substr($text, 0, 70).'...</td>';
						else
							$html .= '	<td>&nbsp;'.$text.'</td>';
					}elseif($propClass == 'DEnum') {
						$html .= '	<td>&nbsp;'.$this->BO->getPropObject($propName)->getDisplayValue().'</td>';
					}else{
						$html .= '	<td>&nbsp;'.$this->BO->get($propName).'</td>';
					}
				}
				if ($propName == 'OID')
					$html .= '	<td>&nbsp;'.$this->BO->getID().'</td>';
			}
		}
		$html .= '</tr>';
		
		$fields['formFields'] = $html;
		
		// View button
		$button = new button("document.location = '".FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID())."'", 'View', 'viewBut');
		$fields['viewButton'] = $button->render();

		$html = '';
		// render edit and delete buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			$html .= '&nbsp;&nbsp;';
			$button = new button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getID())."'", "Edit", "editBut");
			$html .= $button->render();
			$html .= '&nbsp;&nbsp;';
			$button = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.getElementById('delete_form').submit();}", "Delete", "deleteBut");
			$html .= $button->render();
		}
		$fields['adminButtons'] = $html;

		// buffer security fields to $formSecurityFields variable
		$fields['formSecurityFields'] = $this->renderSecurityFields();

		$this->loadTemplate($this->BO, 'list', $fields);
		
		if(method_exists($this, 'after_listView_callback'))
			$this->after_listView_callback();
			
		self::$logger->debug('<<listView');
	}

	/**
	 * Displays a detailed view of the object (read-only)
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function detailedView($fields=array()) {
		self::$logger->debug('>>detailedView(fields=['.print_r($fields, true).'])');
		
		if(method_exists($this, 'before_detailedView_callback'))
			$this->before_detailedView_callback();
		
		global $config;
		
		// we may want to display the OID regardless of class
		$fields['OIDLabel'] = $this->BO->getDataLabel('OID');		
		$fields['OID'] = $this->BO->getID();		
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('view');
		
		// Back button
		$button = new button('history.back()', 'Back', 'backBut');
		$fields['backButton'] = $button->render();
		
		$html = '';
		// render edit and delete buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			$html .= '&nbsp;&nbsp;';
			$button = new button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getID())."'", "Edit", "editBut");
			$html .= $button->render();
			$html .= '&nbsp;&nbsp;';
			$button = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
			$html .= $button->render();
		}
		$fields['adminButtons'] = $html;
		
		$this->loadTemplate($this->BO, 'detail', $fields);
		
		if(method_exists($this, 'after_detailedView_callback'))
			$this->after_detailedView_callback();
			
		self::$logger->debug('<<detailedView');
	}

	/**
	 * Renders the admin view for the business object screen.
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function adminView($fields=array()) {
		self::$logger->debug('>>adminView(fields=['.print_r($fields, true).'])');
		
		if(method_exists($this, 'before_adminView_callback'))
			$this->before_adminView_callback();
		
		global $config;
		
		// the form action
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// the class name of the BO
		$fields['className'] = get_class($this->BO);
		
		// the table name in the DB for the BO
		$fields['tableName'] = $this->BO->getTableName();
		
		// record count for the BO in the DB
		$fields['count'] = ($this->BO->checkTableExists() ? $this->BO->getCount() : '<span class="warning">unavailable</span>');

		// table exists in the DB?
		$fields['tableExists'] = ($this->BO->checkTableExists() ? '<span class="success">Yes</span>' : '<span class="warning">No</span>');
		
		// table schema needs to be updated in the DB?
		$fields['tableNeedsUpdate'] = ($this->BO->checkTableNeedsUpdate() ? '<span class="warning">Yes</span>' : '<span class="success">No</span>');

		// create button
		$button = new button("document.location = '".FrontController::generateSecureURL('act=Create&bo='.get_class($this->BO))."'", "Create New", "createBut");
		$fields['createButton'] = $button->render();
		
		// list all button
		$button = new button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO).'&no-forceframe=true')."'", "List All", "listBut");
		$fields['listButton'] = $button->render();
		
		// the create table button (if required)
		$html = '';
		if (!$this->BO->checkTableExists()) {			
			$button = new button("submit", "Create Table", "createTableBut");
			$html .= $button->render();
			// hidden field so that we know which class to create the table for
			$html .= '<input type="hidden" name="createTableClass" value="'.get_class($this->BO).'"/>';
		}
		$fields['createTableButton'] = $html;
		
		// recreate and update table buttons (if required)
		$html = '';
		if ($this->BO->checkTableNeedsUpdate() && $this->BO->checkTableExists()) {			
			$button = new button("if(confirm('Are you sure you wish to recreate this class table (all data will be lost)?')) {document.getElementById('admin_".get_class($this->BO)."_button_pressed').value = 'recreateTableBut'; document.getElementById('admin_".get_class($this->BO)."').submit();}" , "Recreate Table", "recreateTableBut");
			$html .= $button->render();
			// hidden field so that we know which class to recreate the table for
			$html .= '<input type="hidden" name="recreateTableClass" value="'.get_class($this->BO).'"/>';
			$html .= '&nbsp;&nbsp;';
			$button = new button("if(confirm('Are you sure you wish to attempt to modify this class table by adding new attributes?')) {document.getElementById('admin_".get_class($this->BO)."_button_pressed').value = 'updateTableBut'; document.getElementById('admin_".get_class($this->BO)."').submit();}" , "Update Table", "updateTableBut");
			$html .= $button->render();
			// hidden field so that we know which class to update the table for
			$html .= '<input type="hidden" name="updateTableClass" value="'.get_class($this->BO).'"/>';
			// hidden field to tell us which button was pressed
			$html .= '<input type="hidden" id="admin_'.get_class($this->BO).'_button_pressed" name="admin_'.get_class($this->BO).'_button_pressed" value=""/>';
		}
		$fields['recreateOrUpdateButtons'] = $html;
		
		// buffer security fields to $formSecurityFields variable
		$fields['formSecurityFields'] = $this->renderSecurityFields();
		
		$this->loadTemplate($this->BO, 'admin', $fields);
		
		if(method_exists($this, 'after_adminView_callback'))
			$this->after_adminView_callback();
			
		self::$logger->debug('<<adminView');
	}
	
	/**
	 * Method to render the page header HTML content
	 * 
	 * @param Controller $controller
	 * @return string
	 * @todo remove output buffering around form_validator instance
	 */
	public static function displayPageHead($controller) {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>displayPageHead(controller=['.print_r($controller, true).'])');
		
		if(method_exists($controller, 'before_displayPageHead_callback'))
			$controller->before_displayPageHead_callback();
		
		global $config;
		
		$html = '';
		
		$html.= '<html>';
		$html.= '<head>';
		$html.= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		$html.= '<title>'.$controller->getTitle().'</title>';
		$html.= '<meta name="Keywords" content="'.$controller->getKeywords().'">';
		$html.= '<meta name="Description" content="'.$controller->getDescription().'">';
		$html.= '<meta name="identifier" content="http://'.$config->get('sysURL').'/">';
		$html.= '<meta name="revisit-after" content="7 days">';
		$html.= '<meta name="expires" content="never">';
		$html.= '<meta name="language" content="en">';
		$html.= '<meta name="distribution" content="global">';
		$html.= '<meta name="title" content="'.$controller->getTitle().'">';
		$html.= '<meta name="robots" content="index,follow">';
		$html.= '<meta http-equiv="imagetoolbar" content="no">';

		$html.= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/jquery/ui/themes/'.$config->get('sysTheme').'/ui.all.css">';
		$html.= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/alpha.css">';
		$html.= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'config/css/overrides.css">';

		$html.= '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/jquery-1.3.2.min.js"></script>';
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/contextMenu/jquery.contextMenu.js"></script>';
		
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/ui/jquery.ui.potato.menu.js"></script>';
		
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/ui/jquery-ui-1.7.2.custom.min.js"></script>';
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/ui/ui.datepicker.js"></script>';
				
		ob_start();
		require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
		$html.= ob_get_clean();
		
		// if we are working with a BO, render form validation Javascript rules
		if ($controller->getBO() != null) {
			require_once $config->get('sysRoot').'alpha/view/widgets/StringBox.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/TextBox.js.php';
		
			ob_start();
			require_once $config->get('sysRoot').'alpha/view/widgets/form_validator.js.php';
			$html.= ob_get_clean();

			$html.= '<script type="text/javascript">';
			ob_start();
			$validator = new form_validator($controller->getBO());
			$html.= ob_get_clean();
			$html.= '</script>';
		}
		
		if (isset($_SESSION['currentUser']) && DAO::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && basename($_SERVER['PHP_SELF']) == 'FC.php') {
			$html.= '<script type="text/javascript">';
			$html.= '	(function($) {';
			$html.= '		$(document).ready(function(){';
			$html.= '		$("#adminmenu").ptMenu();';
			$html.= '	});';
			$html.= '})(jQuery);';
			$html.= '</script>';
		}
		
		if(method_exists($controller, 'during_displayPageHead_callback'))
			$html.= $controller->during_displayPageHead_callback();
		
		$html.= '</head>';
		$html.= '<body>';
			
		if($controller->getTitle() != '')
			$html.= '<h1>'.$controller->getTitle().'</h1>';
		
		if (isset($_SESSION['currentUser'])) {	
			$html.= '<p>You are logged in as '.$_SESSION['currentUser']->getDisplayname().'.  <a href="'.$config->get('sysURL').'alpha/controller/Logout.php">Logout</a></p>';
		}else{
			$html.= '<p>You are not logged in</p>';
		}
		
		if (isset($_SESSION['currentUser']) && DAO::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && strpos($_SERVER['REQUEST_URI'], 'FC.php') !== false) {
			$html .= '<ul id="adminmenu" class="potato-menu">
						<li><a href="'.$config->get('sysURL').'">Home</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ListBusinessObjects').'">Admin Home</a></li>
						<li><a href="#">Logs &raquo;</a>			
							<ul>
								<li><a href="'.FrontController::generateSecureURL('act=ViewLog&logPath='.$config->get('sysLogFile')).'">System Log</a></li>
								<li><a href="'.FrontController::generateSecureURL('act=ViewLog&logPath='.$config->get('sysRoot').'logs/search.log').'">Search Log</a></li>
								<li><a href="'.FrontController::generateSecureURL('act=ViewLog&logPath='.$config->get('sysRoot').'logs/feeds.log').'">Feed Log</a></li>
								<li><a href="'.FrontController::generateSecureURL('act=ViewLog&logPath='.$config->get('sysRoot').'logs/tasks.log').'">Cron Tasks Log</a></li>
							</ul>
						</li>
						<li><a href="'.FrontController::generateSecureURL('act=gen_secure_query_strings').'">Generate Secure URL</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ViewMetrics').'">Software Metrics</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=CacheManager').'">Manage Cache</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ListDEnums').'">Manage DEnums</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ViewTestResults').'">Unit Tests</a></li>
					</ul>';
		}
		
		if(method_exists($controller, 'after_displayPageHead_callback'))
			$html.= $controller->after_displayPageHead_callback();
			
		// render a status message if there is any
		$message = $controller->getStatusMessage();
		if(!empty($message))
			$html .= $message;
			
		self::$logger->debug('<<displayPageHead ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render the page footer HTML
	 * 
	 * @param Controller $controller
	 * @return string
	 */
	public static function displayPageFoot($controller) {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>displayPageFoot(controller=['.print_r($controller, true).'])');
		
		$html = '';
		
		if(method_exists($controller, 'before_displayPageFoot_callback'))
			$html .= $controller->before_displayPageFoot_callback();
		
		$html .= '</body>';
		$html .= '</html>';
		
		if(method_exists($controller, 'after_displayPageFoot_callback'))
			$this->after_displayPageFoot_callback();
			
		self::$logger->debug('<<displayPageFoot ['.$html.']');
		return $html;
	}
	
	/**
	 * Renders the HTML for an update (e.g. successful save) message
	 * 
	 * @param string $message
	 * @return string
	 */
	public static function displayUpdateMessage($message) {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>displayUpdateMessage(message=['.$message.'])');
		
		$html = '<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Update:</strong> '.$message.'</p></div>';
		
		self::$logger->debug('<<displayUpdateMessage ['.$html.']');
		return $html;
	}
	
	/**
	 * Renders the HTML for an error (e.g. save failed) message
	 * 
	 * @param string $message
	 * @return string
	 */
	public static function displayErrorMessage($message) {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>displayErrorMessage(message=['.$message.'])');
		
		$html = '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> '.$message.'</p></div>';
		
		self::$logger->debug('<<displayErrorMessage ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render a hidden form for posting the OID of an object to be deleted
	 * 
	 * @return string
	 */
	public static function renderDeleteForm() {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>renderDeleteForm()');
		
		$html = '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" id="delete_form">';		
		$html .= '<input type="hidden" name="delete_oid" id="delete_oid" value=""/>';
		$html .= self::renderSecurityFields();
		$html .= '</form>';
		
		self::$logger->debug('<<renderDeleteForm ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render two hidden, encrypted form fields to be used as
	 * a check to ensure that a post to the controller is being sent from 
	 * the same server as hosting it.
	 * 
	 * @return string 
	 */
	public static function renderSecurityFields() {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>renderSecurityFields()');
		
		$html = '';
		
		$fields = View::generateSecurityFields();
		
		$html .= '<input type="hidden" name="var1" value="'.$fields[0].'"/>';
		$html .= '<input type="hidden" name="var2" value="'.$fields[1].'"/>';
		
		self::$logger->debug('<<renderSecurityFields ['.$html.']');
		return $html;
	}
	
	/**
	 * generates the two security fields to prevent remote form processing
	 * 
	 * @return array an array containing the two fields
	 * @todo move to Controller
	 */
	public static function generateSecurityFields() {
		if(self::$logger == null)
			self::$logger = new Logger('View');
		self::$logger->debug('>>generateSecurityFields()');
		
		// the server hostname + today's date
		$var1 = md5($_SERVER['HTTP_HOST'].date("Ymd"));
		// the server's IP plus $var1
		$var2 = md5($_SERVER['REMOTE_ADDR'].$var1);
		
		self::$logger->debug('<<generateSecurityFields [array('.$var1.', '.$var2.')]');
		return array($var1, $var2);
	}
	
	/**
	 * Method to render the default Integer HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param string $value The field value (optional)
	 * @param bool $tableTags Include table tags and label (optional)
	 * @return string
	 */
	public function renderIntegerField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderIntegerField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
		
				$html .= '<td>';
				$html .= '<input type="text" size="11" name="'.$name.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="11" name="'.$name.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
			}
		}
		
		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
	
				$html .= '<td>';
				$html .= '<input type="text" size="11" name="'.$name.'" value="'.$value.'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="11" name="'.$name.'" value="'.$value.'"/>';
			}
		}
				
		self::$logger->debug('<<renderIntegerField ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render the default Double HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param string $value The field value (optional)
	 * @param bool $tableTags Include table tags and label (optional)
	 * @return string
	 */
	public static function renderDoubleField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderDoubleField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
		
				$html .= '<td>';
				$html .= '<input type="text" size="13" name="'.$name.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="13" name="'.$name.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/>';
			}
		}
		
		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
	
				$html .= '<td>';
				$html .= '<input type="text" size="13" name="'.$name.'" value="'.$value.'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="13" name="'.$name.'" value="'.$value.'"/>';
			}
		}
				
		self::$logger->debug('<<renderDoubleField ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render the default Boolean HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param string $value The field value (optional)
	 * @param bool $tableTags Include table tags and label (optional)
	 * @return string
	 */
	public function renderBooleanField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderBooleanField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
		
				$html .= '<td>';
				$html .= '<select size="1" name="'.$name.'"/>';
				$html .= '<option value="0" selected>N</option>';
				$html .= '<option value="1">Y</option>';
				$html .= '</select><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<select size="1" name="'.$name.'"/>';
				$html .= '<option value="0" selected>N</option>';
				$html .= '<option value="1">Y</option>';
				$html .= '</select>';
			}
		}
		
		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th>';
				$html .= $label;
				$html .= '</th>';
	
				$html .= '<td>';
				$html .= '<select size="1" name="'.$name.'"/>';
				$html .= '<option value="0" '.($value == '0'? 'selected':'').'>N</option>';
				$html .= '<option value="1" '.($value == '1'? 'selected':'').'>Y</option>';
				$html .= '</select><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<select size="1" name="'.$name.'"/>';
				$html .= '<option value="0" '.($value == '0'? 'selected':'').'>N</option>';
				$html .= '<option value="1" '.($value == '1'? 'selected':'').'>Y</option>';
				$html .= '</select>';
			}
		}
				
		self::$logger->debug('<<renderBooleanField ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render the default Enum HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param array $options The Enum options
	 * @param string $value The field value (optional)
	 * @param bool $tableTags Include table tags and label (optional)
	 * @return string
	 */
	public function renderEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
		self::$logger->debug('>>renderEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			if ($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
				$html .= '<td>';
				$html .= '<select name="'.$name.'"/>';			
				foreach ($options as $val) {
					$html .= '<option value="'.$val.'">'.$val.'</option>';
				}
				$html .= '</select><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<select name="'.$name.'"/>';			
				foreach ($options as $val) {
					$html .= '<option value="'.$val.'">'.$val.'</option>';
				}
				$html .= '</select>';
			}
		}
		
		if ($mode == 'edit') {
			if ($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
				$html .= '<td>';
				$html .= '<select name="'.$name.'"/>';			
				foreach ($options as $val) {
					if ($value == $val)
						$html .= '<option value="'.$val.'" selected>'.$val.'</option>';
					else
						$html .= '<option value="'.$val.'">'.$val.'</option>';
				}
				$html .= '</select><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<select name="'.$name.'"/>';			
				foreach ($options as $val) {
					if ($value == $val)
						$html .= '<option value="'.$val.'" selected>'.$val.'</option>';
					else
						$html .= '<option value="'.$val.'">'.$val.'</option>';
				}
				$html .= '</select>';
			}
		}
				
		self::$logger->debug('<<renderEnumField ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render the default DEnum HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param array $options The DEnum options
	 * @param string $value The field value (optional)
	 * @param bool $tableTags Include table tags and label (optional)
	 * @return string
	 */
	public function renderDEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
		self::$logger->debug('>>renderDEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
				$html .= '<td>';
				$html .= '<select name="'.$name.'"/>';
				foreach (array_keys($options) as $index) {
					$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
				}
				$html .= '</select><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<select name="'.$name.'"/>';
				foreach (array_keys($options) as $index) {
					$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
				}
				$html .= '</select>';
			}
		}
		
		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
				$html .= '<td>';
				$html .= '<select name="'.$name.'"/>';			
				foreach (array_keys($options) as $index) {
					if ($value == $index)
						$html .= '<option value="'.$index.'" selected>'.$options[$index].'</option>';
					else
						$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
				}
				$html .= '</select><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<select name="'.$name.'"/>';			
				foreach (array_keys($options) as $index) {
					if ($value == $index)
						$html .= '<option value="'.$index.'" selected>'.$options[$index].'</option>';
					else
						$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
				}
				$html .= '</select>';
			}
		}		
		
		self::$logger->debug('<<renderDEnumField ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render the default field HTML when type is not known
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)	 
	 * @param string $value The field value (optional)
	 * @param bool $tableTags Include table tags and label (optional)
	 * @return string
	 */
	public function renderDefaultField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderDefaultField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			if($tableTags) {
				$html .= '<tr><th colspan="2">';
				$html .= $label;
				$html .= '</th></tr>';
		
				$html .= '<tr><td colspan="2">';
				$html .= '<textarea cols="100" rows="3" name="'.$name.'">'. (isset ($_POST[$name]) ? $_POST[$name] : '').'</textarea><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<textarea cols="100" rows="3" name="'.$name.'">'. (isset ($_POST[$name]) ? $_POST[$name] : '').'</textarea>';
			}
		}
		
		if ($mode == 'edit') {
			if($tableTags) {		
				$html .= '<tr><th colspan="2">';
				$html .= $label;
				$html .= '</th></tr>';
	
				$html .= '<tr><td colspan="2">';
				$html .= '<textarea cols="100" rows="3" name="'.$name.'">'.$value.'</textarea><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<textarea cols="100" rows="3" name="'.$name.'">'.$value.'</textarea>';
			}
		}
		
		if ($mode == 'view') {
			if($tableTags) {
				$html .= '<tr><th>';
				$html .= $label;
				$html .= '</th>';
	
				$html .= '<td>&nbsp;';
				$html .= $value;
				$html .= '</td></tr>';
			}else{
				$html .= $value;
			}
		}
		
		self::$logger->debug('<<renderDefaultField ['.$html.']');
		return $html;
	}
	
	/**
	 * render the default Text HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)	 
	 * @param string $value The field value (optional)
	 * @param bool $tableTags Include table tags and label (optional)
	 * @return string
	 */
	public function renderTextField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderTextField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			// give 10 rows for content fields (other 5 by default)
			if($name == 'content')
				$text = new TextBox($this->BO->getPropObject($name), $label, $name, get_class($this->BO).'_'.$this->BO->getID(), 10);
			else
				$text = new TextBox($this->BO->getPropObject($name), $label, $name, get_class($this->BO).'_'.$this->BO->getID());
			$html .= $text->render($tableTags);
		}
		
		if ($mode == 'edit') {
			// give 10 rows for content fields (other 5 by default)
			if($name == 'content')
				$text = new TextBox($this->BO->getPropObject($name), $label, $name, get_class($this->BO).'_'.$this->BO->getID(), 10);
			else
				$text = new TextBox($this->BO->getPropObject($name), $label, $name, get_class($this->BO).'_'.$this->BO->getID());
			$html .= $text->render($tableTags);
		}
		
		if ($mode == 'view') {
			$html .= '<tr><th>';
			$html .= $label;
			$html .= '</th>';

			// ensures that line returns are rendered
			$value = str_replace("\n", '<br>', $value);
			
			$html .= '<td>&nbsp;';
			$html .= $value;
			$html .= '</td></tr>';
		}
		
		self::$logger->debug('<<renderTextField ['.$html.']');
		return $html;
	}
	
	/**
	 * render the default Relation HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)	 
	 * @param string $value The field value (optional)
	 * @param bool $tableTags Include table tags and label (optional)
	 * @param bool $expanded Render the related fields in expanded format or not (optional)
	 * @param bool $buttons Render buttons for expanding/contacting the related fields (optional)
	 * @return string
	 */
	public function renderRelationField($name, $label, $mode, $value='', $tableTags=true, $expanded=false, $buttons=true) {
		self::$logger->debug('>>renderRelationField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'], expanded=['.$expanded.'], buttons=['.$buttons.'])');
		
		$html = '';
		
		$rel = $this->BO->getPropObject($name);
		
		if ($mode == 'create' || $mode == 'edit') {
			if($rel->getRelationType() == 'MANY-TO-MANY') {
				try{
					// check to see if the rel is on this class
					$rel->getSide(get_class($this->BO));
					$widget = new record_selector($rel, $label, $name, true, get_class($this->BO));
					$html .= $widget->render($tableTags, $expanded, $buttons);
				}catch (IllegalArguementException $iae) {
					// the rel may be on a parent class
					$parentClassName = $this->BO->getTableName().'_object';
					$widget = new record_selector($rel, $label, $name, true, $parentClassName);
					$html .= $widget->render($tableTags, $expanded, $buttons);
				}
			}else{
				$rel = new record_selector($rel, $label, $name);
				$html .= $rel->render($tableTags, $expanded, $buttons);
			}
		}
		
		if ($mode == 'view') {
			if($rel->getRelationType() == 'MANY-TO-ONE') {
				$html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue());
			}elseif($rel->getRelationType() == 'MANY-TO-MANY') {
				try{
					// check to see if the rel is on this class
					$rel->getSide(get_class($this->BO));
					$html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue(get_class($this->BO)));
				}catch (IllegalArguementException $iae) {
					// the rel may be on a parent class
					$parentClassName = $this->BO->getTableName().'_object';
					$html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue($parentClassName));
				}
			}else{
				$rel = new record_selector($rel, $label, $name);
				$html .= $rel->render($tableTags, $expanded, $buttons);
			}
		}
		
		self::$logger->debug('<<renderRelationField ['.$html.']');
		return $html;
	}
	
	/**
	 * Renders all fields for the current BO in edit/create/view mode
	 *
	 * @param string $mode (view|edit|create)
	 * @param array $filterFields Optional list of field names to exclude from rendering
	 * @return string
	 */
	public function renderAllFields($mode, $filterFields=array()) {
		self::$logger->debug('>>renderAllFields(mode=['.$mode.'], filterFields=['.print_r($filterFields, true).'])');
		
		$html = '';
				
		// get the class attributes		
		$properties = array_keys($this->BO->getDataLabels());
		
		foreach($properties as $propName) {			
			if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $filterFields)) {
				$propClass = get_class($this->BO->getPropObject($propName));
				
				// exclude non-Relation transient attributes from create and edit screens
				if($propClass != 'Relation' && ($mode == 'edit' || $mode == 'create') && in_array($propName, $this->BO->getTransientAttributes())) {
					continue;
				}
				
				switch (strtoupper($propClass)) {
					case 'INTEGER' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderIntegerField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
						}				
					break;
					case 'DOUBLE' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderDoubleField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
						}
					break;
					case 'DATE' :
						if($mode == 'view') {
							$value = $this->BO->get($propName);
							if ($value == '0000-00-00')
								$value = '';
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $value);
						}else{
							$date = new calendar($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $date->render();
						}
					break;
					case 'TIMESTAMP' :
						if($mode == 'view') {
							$value = $this->BO->get($propName);
							if ($value == '0000-00-00 00:00:00')
								$value = '';
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $value);
						}else{
							$timestamp = new calendar($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $timestamp->render();
						}
					break;
					case 'STRING' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$string = new StringBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName, get_class($this->BO).'_'.$this->BO->getID());
							$html .= $string->render();
						}
					break;
					case 'TEXT' :
						$html .= $this->renderTextField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
					break;
					case 'BOOLEAN' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderBooleanField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
						}
					break;
					case 'ENUM' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$enum = $this->BO->getPropObject($propName);
							$html .= $this->renderEnumField($propName, $this->BO->getDataLabel($propName), $mode, $enum->getOptions(), $this->BO->get($propName));
						}
					break;
					case 'DENUM' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->getPropObject($propName)->getDisplayValue());
						}else{
							$denum = $this->BO->getPropObject($propName);
							$html .= $this->renderDEnumField($propName, $this->BO->getDataLabel($propName), $mode, $denum->getOptions(), $this->BO->get($propName));
						}
					break;
					case 'RELATION' :
						$html .= $this->renderRelationField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
					break;
					default :
						$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
					break;
				}
			}
		}
		
		self::$logger->debug('<<renderAllFields ['.$html.']');
		return $html;
	}
	
	/**
	 * Loads a .phtml template for the BO specified if one exists.  Lower level custom templates
	 * take precedence.
	 * 
	 * @param object $BO
	 * @param string $mode
	 * @param array $fields 
	 */
	public function loadTemplate($BO, $mode, $fields) {
		self::$logger->debug('>>loadTemplate(BO=['.print_r($BO, true).'], mode=['.$mode.'], fields=['.print_r($fields, true).'])');
		
		global $config;
		
		// for each BO property, create a local variable holding its value		
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			if($propName != 'logger') {
				if(get_class($BO->getPropObject($propName)) == 'DEnum') {					
					${$propName} = $BO->getPropObject($propName)->getDisplayValue();
				}else{
					${$propName} = $BO->get($propName);
				}
			}
		}
		
		// loop over the $fields array and create a local variable for each key value
		foreach (array_keys($fields) as $fieldName)
			${$fieldName} = $fields[$fieldName];
			
		$filename = $mode.'.phtml';
		$classTemplateDir = get_class($BO);

		$customPath = $config->get('sysRoot').'view/templates/'.$classTemplateDir.'/'.$filename;
		$defaultPath1 = $config->get('sysRoot').'alpha/view/templates/'.$classTemplateDir.'/'.$filename;
		$defaultPath2 = $config->get('sysRoot').'alpha/view/templates/'.$filename;

		// Check to see if a custom template exists for this BO, and if it does load that		
		if (file_exists($customPath)) {
			require $customPath;				
		}elseif (file_exists($defaultPath1)) {
			require $defaultPath1;
		}elseif (file_exists($defaultPath2)) {
			require $defaultPath2;	
		}else{
			throw new AlphaException('No ['.$mode.'] HTML template found for class ['.get_class($BO).']');
		}
		
		self::$logger->debug('<<loadTemplate');
	}
}
?>