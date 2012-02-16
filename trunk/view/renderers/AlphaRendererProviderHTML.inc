<?php

/**
 * HTML renderer.  Will invoke widgets from the alpha::view::widgets package
 * automatically for the correct data type.  Templates from ./templates/html
 * loaded by default, but these can be overridden on a per-DAO level in
 * the application when required (consider the default ones to be scaffolding).
 * 
 * 
 * @package alpha::view::renderers
 * @since 1.2
 * @author John Collins <dev@alphaframework.org>
 * @version $Id: AlphaRendererProviderInterface.inc 1499 2012-02-16 19:29:16Z alphadev $
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
class AlphaRendererProviderHTML implements AlphaRendererProviderInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.2
	 */
	private static $logger = null;
	
	/**
	 * The constructor
	 * 
	 * @since 1.2
	 */
	public function __construct() {
		self::$logger = new Logger('AlphaRendererProviderHTML');
		self::$logger->debug('>>__construct()');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::createView()
	 */
	public function createView($fields=array()) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::editView()
	 */
	public function editView($fields=array()) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::listView()
	 */
	public function listView($fields=array()) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::detailedView()
	 */
	public function detailedView($fields=array()) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::adminView()
	 */
	public function adminView($fields=array()) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayPageHead()
	 */
	public static function displayPageHead($controller) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayPageFoot()
	 */
	public static function displayPageFoot($controller) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayUpdateMessage()
	 */
	public static function displayUpdateMessage($message) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayErrorMessage()
	 */
	public static function displayErrorMessage($message) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderErrorPage()
	 */
	public static function renderErrorPage($code, $message) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDeleteForm()
	 */
	public static function renderDeleteForm() {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderSecurityFields()
	 */
	public static function renderSecurityFields() {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderIntegerField()
	 */
	public function renderIntegerField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDoubleField()
	 */
	public static function renderDoubleField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderBooleanField()
	 */
	public function renderBooleanField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderEnumField()
	 */
	public static function renderEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDEnumField()
	 */
	public static function renderDEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDefaultField()
	 */
	public function renderDefaultField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderTextField()
	 */
	public function renderTextField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderRelationField()
	 */
	public function renderRelationField($name, $label, $mode, $value='', $tableTags=true, $expanded=false, $buttons=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
} 

?>