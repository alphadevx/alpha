<?php

/**
 *
 * A factory for creating rendering provider implementations that implement the
 * AlphaRendererProviderInterface interface.
 *
 * @package alpha::view::renderers
 * @since 1.2
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
class AlphaRendererProviderFactory {
	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.2
	 */
	private static $logger = null;

	/**
	 * A static method that attempts to return a AlphaRendererProviderInterface instance
	 * based on the name of the provider class supplied.
	 *
	 * @param $providerName The class name of the provider class, should be in one of the alpha::view::renderers::[html|json|xml] packages.
	 * @param $BO The AlphaDAO instance to pass to the renderer provider for passing data.
	 * @throws IllegalArguementException
	 * @return AlphaRendererProviderInterface
	 * @since 1.2
	 */
	public static function getInstance($providerName, $BO) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaRendererProviderFactory');

		self::$logger->debug('>>getInstance(providerName=['.$providerName.'])');

		global $config;

		if($providerName == 'auto') {
			if(isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/json') {
				// use the JSON renderer
				$providerName = 'AlphaRendererProviderJSON';
			}else{
				// use the HTML renderer
				$providerName = 'AlphaRendererProviderHTML';
			}
		}

		if(file_exists($config->get('app.root').'alpha/view/renderers/html/'.$providerName.'.inc')) {
			require_once $config->get('app.root').'alpha/view/renderers/html/'.$providerName.'.inc';
		}elseif(file_exists($config->get('app.root').'alpha/view/renderers/xml/'.$providerName.'.inc')) {
            require_once $config->get('app.root').'alpha/view/renderers/xml/'.$providerName.'.inc';
        }elseif(file_exists($config->get('app.root').'alpha/view/renderers/json/'.$providerName.'.inc')) {
            require_once $config->get('app.root').'alpha/view/renderers/json/'.$providerName.'.inc';
		}else{
			throw new IllegalArguementException('The class ['.$providerName.'] is not defined anywhere!');
		}

		$instance = new $providerName;
        $instance->setBO($BO);

        if(!$instance instanceof AlphaRendererProviderInterface)
	        throw new IllegalArguementException('The class ['.$providerName.'] does not implement the expected AlphaRendererProviderInterface interface!');

        self::$logger->debug('<<getInstance: [Object '.$providerName.']');
        return $instance;

		self::$logger->debug('<<getInstance');
	}
}

?>