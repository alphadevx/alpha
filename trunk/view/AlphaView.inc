<?php

/**
 *
 * The master rendering view class for the Alpha Framework.
 * 
 * @package alpha::view
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).  
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
class AlphaView {
	/**
	 * The business object that will be rendered
	 * 
	 * @var AlphaDAO
	 * @since 1.0
	 */
	protected $BO;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * Constructor for the AlphaView.  As this is protected, use the AlphaView::getInstance method from a public scope.
	 * 
	 * @param AlphaDAO $BO
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	protected function __construct($BO) {
		self::$logger = new Logger('AlphaView');
		self::$logger->debug('>>__construct(BO=['.var_export($BO, true).'])');
		
		if(AlphaDAO::checkClassDefExists(get_class($BO)))
			$this->BO = $BO;
		else
			throw new IllegalArguementException('The BO provided ['.get_class($BO).'] is not defined anywhere!');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Static method which returns a AlphaView object or a custom child view for the BO specified
	 * if one exists
	 * 
	 * @param AlphaDAO $BO The main business object that this view is going to render
	 * @param boolean $returnParent Flag to enforce the return of this object instead of a child (defaults to false)
	 * @return AlphaView Returns a AlphaView object, or a child view object from the /view directory if one exists for this BO
	 * @since 1.0
	 */
	public static function getInstance($BO, $returnParent=false) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaView');
		self::$logger->debug('>>getInstance(BO=['.var_export($BO, true).'], returnParent=['.$returnParent.'])');
		
		global $config;
		
		$filename = get_class($BO);
		// remove the Object part
		$filename = str_replace('Object', '', $filename);
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
				self::$logger->debug('<<getInstance [new AlphaView('.get_class($BO).', true)]');
				return new AlphaView($BO, true);
			}
		}else{
			self::$logger->debug('<<getInstance [new AlphaView('.get_class($BO).', true)]');
			return new AlphaView($BO, true);
		}
	}

	/**
	 * Simple setter for the view business object
	 * 
	 * @param AlphaDAO $BO
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function setBO($BO) {		
		self::$logger->debug('>>setBO(BO=['.var_export($BO, true).'])');
		
		if(AlphaDAO::checkClassDefExists(get_class($BO)))
			$this->BO = $BO;
		else
			throw new IllegalArguementException('The BO provided ['.get_class($BO).'] is not defined anywhere!');
		
		self::$logger->debug('<<setBO');
	}
	
	/**
	 * Gets the BO attached to this view (if any)
	 * 
	 * @return AlphaDAO
	 * @since 1.0
	 */
	public function getBO() {
		return $this->BO;
	}

	/**
	 * Renders the default create view to standard output
	 * 
	 * @param array $fields Hash array of fields to pass to the template
	 * @since 1.0
	 */
	public function createView($fields=array()) {		
		self::$logger->debug('>>createView(fields=['.var_export($fields, true).'])');
		
		if(method_exists($this, 'before_createView_callback'))
			$this->before_createView_callback();
		
		global $config; 
			
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), $this->BO);
		$body = $provider->createView($fields);
		
		echo $body;
		
		if(method_exists($this, 'after_createView_callback'))
			$this->after_createView_callback();
			
		self::$logger->debug('<<createView');
	}

	/**
	 * Renders a form to enable object editing to standard output
	 * 
	 * @param array $fields Hash array of fields to pass to the template
	 * @since 1.0
	 */
	public function editView($fields=array()) {
		self::$logger->debug('>>editView(fields=['.var_export($fields, true).'])');
		
		if(method_exists($this, 'before_editView_callback'))
			$this->before_editView_callback();
		
		global $config; 
			
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), $this->BO);
		$body = $provider->editView($fields);
		
		echo $body;
		
		if(method_exists($this, 'after_editView_callback'))
			$this->after_editView_callback();
			
		self::$logger->debug('<<editView');
	}

	/**
	 * Renders the list view to standard output
	 * 
	 * @param array $fields Hash array of fields to pass to the template
	 * @since 1.0
	 */
	public function listView($fields=array()) {
		self::$logger->debug('>>listView(fields=['.var_export($fields, true).'])');
		
		if(method_exists($this, 'before_listView_callback'))
			$this->before_listView_callback();
		
		global $config; 
			
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), $this->BO);
		$body = $provider->listView($fields);
		
		echo $body;
		
		if(method_exists($this, 'after_listView_callback'))
			$this->after_listView_callback();
			
		self::$logger->debug('<<listView');
	}

	/**
	 * Displays a detailed view of the object (read-only) to standard output
	 * 
	 * @param array $fields Hash array of fields to pass to the template
	 * @since 1.0
	 */
	public function detailedView($fields=array()) {
		self::$logger->debug('>>detailedView(fields=['.var_export($fields, true).'])');
		
		if(method_exists($this, 'before_detailedView_callback'))
			$this->before_detailedView_callback();
		
		global $config; 
			
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), $this->BO);
		$body = $provider->detailedView($fields);
		
		echo $body;
		
		if(method_exists($this, 'after_detailedView_callback'))
			$this->after_detailedView_callback();
			
		self::$logger->debug('<<detailedView');
	}

	/**
	 * Renders the admin view for the business object screen to standard output
	 * 
	 * @param array $fields Hash array of fields to pass to the template
	 * @since 1.0
	 */
	public function adminView($fields=array()) {
		self::$logger->debug('>>adminView(fields=['.var_export($fields, true).'])');
		
		if(method_exists($this, 'before_adminView_callback'))
			$this->before_adminView_callback();
		
		global $config; 
			
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), $this->BO);
		$body = $provider->adminView($fields);
		
		echo $body;
		
		if(method_exists($this, 'after_adminView_callback'))
			$this->after_adminView_callback();
			
		self::$logger->debug('<<adminView');
	}
	
	/**
	 * Method to render the page header content
	 * 
	 * @param AlphaController $controller
	 * @return string
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public static function displayPageHead($controller) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaView');
		self::$logger->debug('>>displayPageHead(controller=['.var_export($controller, true).'])');
		
		if(method_exists($controller, 'before_displayPageHead_callback'))
			$controller->before_displayPageHead_callback();
		
		global $config; 
			
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), new PersonObject());
		eval('$header = '.get_class($provider).'::displayPageHead($controller);');
		
		if(method_exists($controller, 'after_displayPageHead_callback'))
			$header.= $controller->after_displayPageHead_callback();
			
		self::$logger->debug('<<displayPageHead ['.$header.']');
		return $header;
	}
	
	/**
	 * Method to render the page footer content
	 * 
	 * @param AlphaController $controller
	 * @return string
	 * @since 1.0
	 */
	public static function displayPageFoot($controller) {
		if(self::$logger == null)		
			self::$logger = new Logger('AlphaView');
		
		self::$logger->debug('>>displayPageFoot(controller=['.get_class($controller).'])');
		
		global $config;
		
		$footer = '';
		
		if(method_exists($controller, 'before_displayPageFoot_callback'))
			$footer .= $controller->before_displayPageFoot_callback();
		
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), new PersonObject());
		eval('$footer .= '.get_class($provider).'::displayPageFoot($controller);');
		
		if(method_exists($controller, 'after_displayPageFoot_callback'))
			$footer .= $controller->after_displayPageFoot_callback();
			
		self::$logger->debug('<<displayPageFoot ['.$footer.']');
		return $footer;
	}
	
	/**
	 * Renders the content for an update (e.g. successful save) message
	 * 
	 * @param string $message
	 * @return string
	 * @since 1.0
	 */
	public static function displayUpdateMessage($message) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaView');
		self::$logger->debug('>>displayUpdateMessage(message=['.$message.'])');
		
		global $config;
		
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), new PersonObject());
		eval('$message = '.get_class($provider).'::displayUpdateMessage($message);');
		
		self::$logger->info('<<displayUpdateMessage ['.$message.']');
		return $message;
	}
	
	/**
	 * Renders the content for an error (e.g. save failed) message
	 * 
	 * @param string $message
	 * @return string
	 * @since 1.0
	 */
	public static function displayErrorMessage($message) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaView');
		self::$logger->debug('>>displayErrorMessage(message=['.$message.'])');
		
		global $config;
		
		$provider = AlphaRendererProviderFactory::getInstance($config->get('sysRendererProviderName'), new PersonObject());
		eval('$message = '.get_class($provider).'::displayErrorMessage($message);');
		
		self::$logger->debug('<<displayErrorMessage ['.$message.']');
		return $message;
	}
	
	/**
	 * Renders a HTML error page with the supplied error code (typlically a HTTP code) and a message
	 * 
	 * @param string $code
	 * @param string $message
	 * @return string
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
	 */
	public static function renderErrorPage($code, $message) {
		global $config;		
		
		$html = '<html><head>';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/jquery/ui/themes/'.$config->get('sysTheme').'/jquery.ui.all.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/css/alpha.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'config/css/overrides.css">';
		$html .= '<title>'.$code.' - '.$message.'</title></head>';
		$html .= '<body>';
		$html .= '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>'.$code.':</strong> '.$message.'</p>
				</div>';
		$html .= '</body></html>';
		
		return $html;
	}
	
	/**
	 * Method to render a hidden form for posting the OID of an object to be deleted
	 * 
	 * @return string
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
	 */
	public static function renderDeleteForm() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaView');
		self::$logger->debug('>>renderDeleteForm()');
		
		$html = '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" id="deleteForm">';		
		$html .= '<input type="hidden" name="deleteOID" id="deleteOID" value=""/>';
		$html .= self::renderSecurityFields();
		$html .= '</form>';
		
		self::$logger->debug('<<renderDeleteForm ['.$html.']');
		return $html;
	}
	
	/**
	 * Method to render a HTML form with two hidden, hashed (MD5) form fields to be used as
	 * a check to ensure that a post to the controller is being sent from the same server 
	 * as hosting it.
	 * 
	 * @return string
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
	 */
	public static function renderSecurityFields() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaView');
		self::$logger->debug('>>renderSecurityFields()');
		
		$html = '';
		
		$fields = AlphaController::generateSecurityFields();
		
		$html .= '<input type="hidden" name="var1" value="'.$fields[0].'"/>';
		$html .= '<input type="hidden" name="var2" value="'.$fields[1].'"/>';
		
		self::$logger->debug('<<renderSecurityFields ['.$html.']');
		return $html;
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
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
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
				$html .= '<input type="text" style="width:100%;" name="'.$name.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" style="width:100%;" name="'.$name.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
			}
		}
		
		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
	
				$html .= '<td>';
				$html .= '<input type="text" style="width:100%;" name="'.$name.'" value="'.$value.'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" style="width:100%;" name="'.$name.'" value="'.$value.'"/>';
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
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
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
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
	 */
	public function renderBooleanField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderBooleanField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			if($tableTags) {		
				$html .= '<tr><th style="width:25%;">';
				$html .= '<label for="'.$name.'">'.$label.'</label>';
				$html .= '</th>';
				
				$html .= '<td>';
				$html .= '<input type="hidden" name="'.$name.'" value="0">';
				$html .= '<input type="checkbox" name="'.$name.'" id="'.$name.'" />';
				$html .= '</td></tr>';
			}else{
				$html .= $label.'<select size="1" name="'.$name.'"/>';
				$html .= '<option value="0" selected>No</option>';
				$html .= '<option value="1">Yes</option>';
				$html .= '</select>';
			}
		}
		
		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= '<label for="'.$name.'">'.$label.'</label>';
				$html .= '</th>';
				
				$html .= '<td>';
				$html .= '<input type="hidden" name="'.$name.'" value="0">';
				$html .= '<input type="checkbox" name="'.$name.'" id="'.$name.'"'.($value == '1'? ' checked':'').' />';
				$html .= '</td></tr>';
			}else{
				$html .= '<label for="'.$name.'">'.$label.'</label>';
				$html .= '<input type="hidden" name="'.$name.'" value="0">';
				$html .= '<input type="checkbox" name="'.$name.'" id="'.$name.'"'.($value == '1'? ' checked':'').' />';
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
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
	 */
	public static function renderEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
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
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
	 */
	public static function renderDEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
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
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
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
				if(method_exists($this, 'during_renderDefaultField_callback'))
					$html .= $this->during_renderDefaultField_callback($name, $mode, $value);
				else
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
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
	 */
	public function renderTextField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderTextField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');
		
		$html = '';
		
		if ($mode == 'create') {
			// give 10 rows for content fields (other 5 by default)
			if($name == 'content')
				$text = new TextBox($this->BO->getPropObject($name), $label, $name, 10);
			else
				$text = new TextBox($this->BO->getPropObject($name), $label, $name);
			$html .= $text->render($tableTags);
		}
		
		if ($mode == 'edit') {
			// give 10 rows for content fields (other 5 by default)
			if($name == 'content') {
				$viewState = ViewState::getInstance();
				
				if($viewState->get('markdownTextBoxRows') == '')
					$text = new TextBox($this->BO->getPropObject($name), $label, $name, 10);
				else
					$text = new TextBox($this->BO->getPropObject($name), $label, $name, (integer)$viewState->get('markdownTextBoxRows'));
					
				$html .= $text->render($tableTags, true);
			}else{
				$text = new TextBox($this->BO->getPropObject($name), $label, $name);
				$html .= $text->render($tableTags);
			}
		}
		
		if ($mode == 'view') {
			if($tableTags)
				$html .= '<tr><th>';
				
			$html .= $label;
			
			if($tableTags)
				$html .= '</th>';
			
			// filter ouput to prevent malicious injection
			$value = InputFilter::encode($value);

			// ensures that line returns are rendered
			$value = str_replace("\n", '<br>', $value);
			
			if($tableTags)
				$html .= '<td>&nbsp;';
				
			$html .= $value;
			
			if($tableTags)
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
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
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
					$widget = new RecordSelector($rel, $label, $name, get_class($this->BO));
					$html .= $widget->render($tableTags, $expanded, $buttons);
				}catch (IllegalArguementException $iae) {
					// the rel may be on a parent class
					$parentClassName = ucfirst($this->BO->getTableName()).'Object';
					$widget = new RecordSelector($rel, $label, $name, $parentClassName);
					$html .= $widget->render($tableTags, $expanded, $buttons);
				}
			}else{
				$rel = new RecordSelector($rel, $label, $name);
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
					$parentClassName = ucfirst($this->BO->getTableName()).'Object';
					$html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue($parentClassName));
				}
			}else{
				$rel = new RecordSelector($rel, $label, $name);
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
	 * @param array $readOnlyFields Optional list of fields to render in a readonly fashion when rendering in create or edit mode
	 * @return string
	 * @since 1.0
	 * @todo use version from AlphaRendererProviderInterface instance
	 */
	public function renderAllFields($mode, $filterFields=array(), $readOnlyFields=array()) {
		self::$logger->debug('>>renderAllFields(mode=['.$mode.'], filterFields=['.var_export($filterFields, true).'], readOnlyFields=['.var_export($readOnlyFields, true).'])');
		
		$html = '';
				
		// get the class attributes		
		$properties = array_keys($this->BO->getDataLabels());
		
		$orignalMode = $mode;
		
		foreach($properties as $propName) {			
			if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $filterFields)) {
				// render readonly fields in the supplied array
				if(in_array($propName, $readOnlyFields))
					$mode = 'view';
				else
					$mode = $orignalMode;
					
				if(!is_object($this->BO->getPropObject($propName)))
					continue;
					
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
							$date = new DateBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
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
							$timestamp = new DateBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $timestamp->render();
						}
					break;
					case 'STRING' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$string = new StringBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
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
	 * Loads a template for the BO specified if one exists.  Lower level custom templates
	 * take precedence.
	 * 
	 * @param AlphaDAO $BO
	 * @param string $mode
	 * @param array $fields
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function loadTemplate($BO, $mode, $fields) {
		self::$logger->debug('>>loadTemplate(BO=['.var_export($BO, true).'], mode=['.$mode.'], fields=['.var_export($fields, true).'])');
		
		global $config;
		
		// for each BO property, create a local variable holding its value		
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			if($propName != 'logger' && !$propObj->isPrivate()) {
				$prop = $BO->getPropObject($propName);
				if($prop instanceof DEnum) {					
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
			self::$logger->debug('Loading template ['.$customPath.']');
			require $customPath;				
		}elseif (file_exists($defaultPath1)) {
			self::$logger->debug('Loading template ['.$defaultPath1.']');
			require $defaultPath1;
		}elseif (file_exists($defaultPath2)) {
			self::$logger->debug('Loading template ['.$defaultPath2.']');
			require $defaultPath2;	
		}else{
			throw new IllegalArguementException('No ['.$mode.'] HTML template found for class ['.get_class($BO).']');
		}
		
		self::$logger->debug('<<loadTemplate');
	}
	
	/**
	 * Loads a template fragment from the renderers/templates/[type]/fragment/[filename.ext] location.
	 * 
	 * @param string $type Currently only html support, later json and xml.
	 * @param string $fileName The name of the fragment file
	 * @param array $fields A hash array of field values to pass to the template fragment.
	 * @since 1.2
	 * @throws IllegalArguementException
	 */
	public static function loadTemplateFragment($type, $fileName, $fields) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaView');
		self::$logger->debug('>>loadTemplateFragment(type=['.$type.'], fileName=['.$fileName.'], fields=['.var_export($fields, true).'])');
		
		global $config;
		
		// loop over the $fields array and create a local variable for each key value
		foreach (array_keys($fields) as $fieldName)
			${$fieldName} = $fields[$fieldName];

		$customPath = $config->get('sysRoot').'view/renderers/templates/'.$type.'/fragments/'.$fileName;
		$defaultPath = $config->get('sysRoot').'alpha/view/renderers/templates/'.$type.'/fragments/'.$fileName;

		// Check to see if a custom template exists for this BO, and if it does load that		
		if (file_exists($customPath)) {
			self::$logger->debug('Loading template ['.$customPath.']');
			require $customPath;				
		}elseif (file_exists($defaultPath)) {
			self::$logger->debug('Loading template ['.$defaultPath.']');
			require $defaultPath;
		}else{
			throw new IllegalArguementException('Template fragment not found in ['.$customPath.'] or ['.$defaultPath.']!');
		}
		
		self::$logger->debug('<<loadTemplateFragment');
	}
}

?>