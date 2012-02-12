<?php

require_once $config->get('sysRoot').'alpha/util/codehighlight/AlphaHighlightProviderInterface.inc';

/**
 *
 * Wraps Luminous in the standard API defined in AlphaHighlightProviderInterface, 
 * for use in the Alpha CMS module.  Note that Luminous is not included in Alpha,
 * so you will need to download it from here:
 * 
 * (PHP 5.3)
 * 
 * http://luminous.asgaard.co.uk/index.php/page/index
 * 
 * (PHP 5.2, you will need to use the older version)
 * 
 * http://code.google.com/p/luminous/
 * 
 * Once downloaded, place in the [application]/lib/luminous directory which is where this class
 * will attempt to load it from.
 * 
 * Also note that Luminous requires it's own .css file to be loaded in the page that is displaying
 * the highlighted code.  The following callback in your controller will inject the desired .css
 * file:
 * 
 * public function during_displayPageHead_callback() {
 *		global $config;
 *	
 *		$html = '<link type="text/css" rel="StyleSheet" href="'.$config->get('sysURL').'lib/luminous/style/luminous.css">';
 *	
 *		return $html;
 *	}
 * 
 * @package alpha::util::codehighlight
 * @since 1.2
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
class AlphaHighlightProviderLuminous implements AlphaHighlightProviderInterface {
	/**
	 * (non-PHPdoc)
	 * @see alpha/util/codehighlight/AlphaHighlightProviderInterface::highlight()
	 * @since 1.2
	 */
	public function highlight($source, $language) {
		global $config;
		
		$codeBlock = '';
		
		try {
			AlphaAutoLoader::loadLib('luminous.php');
			
			$codeblock = luminous($language, $source, false);
		}catch(LibraryNotInstalledException $e) {
			$codeblock = $source;
		}
		
		return $codeblock;
	}
}
?>