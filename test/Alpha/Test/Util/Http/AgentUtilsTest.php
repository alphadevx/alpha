<?php

namespace Alpha\Test\Util\Http;

use Alpha\Util\Http\AgentUtils;

/**
 *
 * Test cases for the AgentUtils class
 *
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class AgentUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing various browser agent strings to ensure that they are not mistakenly threated as bots
     */
    public function testIsBotFalse()
    {
    	$this->assertFalse(AgentUtils::isBot('Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)'), 'testing that IE 6 is not a bot');
    	$this->assertFalse(AgentUtils::isBot('Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2) Gecko/20100115 Firefox/3.6'), 'testing that FF 3.6 is not a bot');
    }

	/**
     * Testing various spider bot agent strings to ensure that they are correctly threated as bots
     */
    public function testIsBotTrue()
    {
    	$this->assertTrue(AgentUtils::isBot('Googlebot/2.1 (+http://www.googlebot.com/bot.html)'), 'testing that Google is a bot');
    	$this->assertTrue(AgentUtils::isBot('Gigabot/1.0'), 'testing that Gigabot is a bot');
    }
}

?>