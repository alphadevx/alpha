<?php

require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 *
 * The rendering class for the Sequence class
 * 
 * @package alpha::view
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class SequenceView extends AlphaView {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * Constructor
	 * 
	 * @param AlphaDAO $BO
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	protected function __construct($BO) {
		self::$logger = new Logger('SequenceView');
		self::$logger->debug('>>__construct(BO=['.print_r($BO, true).'])');
		
		parent::__construct($BO);
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Custom list view
	 * 
	 * @param array $fields Hash array of HTML fields to pass to the template
	 * @since 1.0
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
					$html .= '	<td>&nbsp;'.$this->BO->getOID().'</td>';
			}
		}
		$html .= '</tr>';
		
		$fields['formFields'] = $html;
		
		// View button
		if(strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getOID())."';", 'View', 'viewBut');
			$fields['viewButton'] = $button->render();
		}else{
			$button = new Button("document.location = '".$this->BO->get('URL')."';", 'View', 'viewBut');
			$fields['viewButton'] = $button->render();
		}
		
		// supressing the edit/delete buttons for Sequences
		$fields['adminButtons'] = '';

		// buffer security fields to $formSecurityFields variable
		$fields['formSecurityFields'] = $this->renderSecurityFields();

		$this->loadTemplate($this->BO, 'list', $fields);
		
		if(method_exists($this, 'after_listView_callback'))
			$this->after_listView_callback();
			
		self::$logger->debug('<<listView');
	}
	
	/**
	 * Custom display view
	 * 
	 * @param array $fields Hash array of HTML fields to pass to the template
	 * @since 1.0
	 */
	public function detailedView($fields=array()) {
		self::$logger->debug('>>detailedView(fields=['.print_r($fields, true).'])');
		
		if(method_exists($this, 'before_detailedView_callback'))
			$this->before_detailedView_callback();
		
		global $config;
		
		// we may want to display the OID regardless of class
		$fields['OIDLabel'] = $this->BO->getDataLabel('OID');		
		$fields['OID'] = $this->BO->getOID();		
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('view');
		
		// Back button
		$button = new Button('history.back()', 'Back', 'backBut');
		$fields['backButton'] = $button->render();
		
		$fields['adminButtons'] = '';
		
		$this->loadTemplate($this->BO, 'detail', $fields);
		
		if(method_exists($this, 'after_detailedView_callback'))
			$this->after_detailedView_callback();
			
		self::$logger->debug('<<detailedView');
	}
}

?>