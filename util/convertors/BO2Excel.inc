<?php

/**
 *
 * Class for converting a Business Object to an Excel spreadsheet
 * 
 * @package alpha::util::convertors
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class BO2Excel {
	/**
	 * The Business Object we will convert to an Excel sheet
	 *
	 * @var DAO
	 */
	private $BO;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * Constructor
	 *
	 * @param object $BO
	 */
	public function __construct($BO) {
		if(self::$logger == null)
			self::$logger = new Logger('BO2Excel');
		self::$logger->debug('>>__construct(BO=['.print_r($BO, true).'])');
		
		$this->BO = $BO;
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Sends the output as an Excel spreadsheet to standard output
	 */
	public function render() {
		self::$logger->debug('>>render()');
		
		// the name of the file download
		$fileName = $this->BO->getTableName().'-'.$this->BO->getId();
		
		//header info for browser
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename='.$fileName.'.xls');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		//define separator (tabbed character)
		$sep = "\t";
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		
		// print headers
		foreach($properties as $propObj) {
			$propName = $propObj->name;//print_r($this->BO->getDefaultAttributes());
			if (!in_array($propName, $this->BO->getTransientAttributes()) && !in_array($propName, $this->BO->getDefaultAttributes())) {
				echo $this->BO->getDataLabel($propName).$sep;
			}
		}
		
		echo "\n";
		
		// print values
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			$prop = $this->BO->getPropObject($propName);
			if (!in_array($propName, $this->BO->getTransientAttributes()) && !in_array($propName, $this->BO->getDefaultAttributes())) {
				if(get_class($prop) == 'DEnum')
					echo $prop->getDisplayValue().$sep;
				else
					echo $prop->getValue().$sep;
			}
		}
		
		
		echo "\n";		
		
		self::$logger->debug('<<render');
	}	
}

?>