<?php

namespace Alpha\Test\Controller\Front;

use Alpha\Controller\Front\FrontController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Filter\ClientBlacklistFilter;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\BadRequest;

/**
 *
 * Test cases for the FrontController class.
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
class FrontControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A controller token to test with
     *
     * @var string
     * @since 1.0
     */
    //private $token;

    /**
     * Set up tests
     *
     * @since 1.0
     */
    /*protected function setUp() {
        if (!isset($this->token))
            $this->token = $_GET['tk'];
        $_GET['tk'] = null;
        $_GET['act'] = null;
    }

    /**
     * Tear down tests
     *
     * @since 1.0
     */
   /* protected function tearDown() {
        $_GET['tk'] = $this->token;
    }

    /**
     * Testing that the constructor will detect the page controller action we want to invoke from the global _GET array
     *
     * @since 1.0
     */
    public function testConstructActParam()
    {
        $_GET['act'] = 'LogController';
        $front = new FrontController();

        $this->assertEquals('LogController', $front->getPageController(), 'testing that the constructor will detect the page controller action we want to invoke from the global _GET array');
    }

    /**
     * Testing that the constructor can parse the correct page controller action from a mod_rewrite style URL
     *
     * @since 1.0
     */
    public function testConstructModRewrite()
    {
        $_SERVER['REQUEST_URI'] = 'LogController';
        $front = new FrontController();

        $this->assertEquals('LogController', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from a mod_rewrite style URL');
    }

    /**
     * Testing that the constructor can parse the correct page controller action from a mod_rewrite style URL when a controller alias is used
     *
     * @since 1.0
     */
    public function testConstructModRewriteWithAlias()
    {
        $_SERVER['REQUEST_URI'] = 'log/path-to-file';
        $front = new FrontController();
        $front->registerAlias('LogController','log','path');

        $this->assertEquals('LogController', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from a mod_rewrite style URL when a controller alias is used');
    }

    /**
     * Testing that the constructor can parse the correct page controller action from an encrypted token param
     *
     * @since 1.0
     */
    public function testConstructorWithEncryptedToken()
    {
        $params = 'act=ViewArticleTitle&title=Test_Title';
        $_GET['tk'] = FrontController::encodeQuery($params);
        $front = new FrontController();

        $this->assertEquals('ViewArticleTitle', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from an encrypted token param');
    }

    /**
     * Testing that the constructor can parse the correct page controller action from an encrypted token param provided on a mod-rewrite style URL
     *
     * @since 1.0
     */
    public function testConstructorModRewriteWithEncryptedToken()
    {
        $params = 'act=ViewArticleTitle&title=Test_Title';
        $_SERVER['REQUEST_URI'] = 'tk/'.FrontController::encodeQuery($params);
        $front = new FrontController();

        $this->assertEquals('ViewArticleTitle', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from an encrypted token param provided on a mod-rewrite style URL');
    }

    /**
     * Testing the encodeQuery method with a known encrypted result for a test key
     *
     * @since 1.0
     */
    public function testEncodeQuery()
    {
        $config = ConfigProvider::getInstance();

        $oldKey = $config->get('security.encryption.key');
        $config->set('security.encryption.key', 'testkey');
        $params = 'act=ViewArticleTitle&title=Test_Title';

        $this->assertEquals(FrontController::encodeQuery($params), '8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==', 'testing the encodeQuery method with a known encrypted result for a test key');

        $config->set('security.encryption.key', $oldKey);
    }

    /**
     * Testing the decodeQueryParams method with a known encrypted result for a test key
     *
     * @since 1.0
     */
    public function testDecodeQueryParams()
    {
        $config = ConfigProvider::getInstance();

        $oldKey = $config->get('security.encryption.key');
        $config->set('security.encryption.key', 'testkey');
        $tk = '8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==';

        $this->assertEquals('act=ViewArticleTitle&title=Test_Title', FrontController::decodeQueryParams($tk), 'testing the decodeQueryParams method with a known encrypted result for a test key');

        $config->set('security.encryption.key', $oldKey);
    }

    /**
     * Testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key
     *
     * @since 1.0
     */
    public function testGetDecodeQueryParams()
    {
        $config = ConfigProvider::getInstance();

        $oldKey = $config->get('security.encryption.key');
        $config->set('security.encryption.key', 'testkey');
        $tk = '8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==';

        $decoded = FrontController::getDecodeQueryParams($tk);

        $this->assertEquals('ViewArticleTitle', $decoded['act'], 'testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key');
        $this->assertEquals('Test_Title', $decoded['title'], 'testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key');

        $config->set('security.encryption.key', $oldKey);
    }

    /**
     * Testing that a request to a bad URL will result in a ResourceNotFoundException exception
     *
     * @since 1.0
     */
    public function testLoadControllerFileNotFound()
    {
        $config = ConfigProvider::getInstance();

        $_SERVER['REQUEST_URI'] = 'doesNotExists';
        $front = new FrontController();

        $badrequest = new BadRequest();
        if (!$badrequest->checkTableExists())
            $badrequest->makeTable();

        try {
            $front->loadController(false);
            $this->fail('testing that a request to a bad URL will result in a ResourceNotFoundException exception');
        } catch (ResourceNotFoundException $e) {
            $this->assertTrue($e->getMessage() != '', 'testing that a request to a bad URL will result in a ResourceNotFoundException exception');
        }
    }

    /**
     * Testing the setting up and checking for the existence of a controller alias
     *
     * @since 1.0
     */
    public function testDefineAlias()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $front = new FrontController();
        $front->registerAlias('ViewArticleTitle','article','title');

        $this->assertTrue($front->hasAlias('ViewArticleTitle'), 'testing the setting up and checking for the existence of a controller alias');
        $this->assertTrue($front->checkAlias('article'), 'testing the setting up and checking for the existence of a controller alias');
        $this->assertEquals('ViewArticleTitle', $front->getAliasController('article'),
            'testing the setting up and checking for the existence of a controller alias');
        $this->assertEquals('article', $front->getControllerAlias('ViewArticleTitle'),
            'testing the setting up and checking for the existence of a controller alias');
    }

    /**
     * Testing the accessing of the expected param for a given alias/controller
     *
     * @since 1.0
     */
    public function testAccessingAliasParamNames()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $front = new FrontController();
        $front->registerAlias('ViewArticleTitle','article','title');

        $this->assertEquals('title', $front->getAliasParam('article'), 'testing the accessing of the expected param for a given alias/controller');
        $this->assertEquals('title', $front->getControllerParam('ViewArticleTitle'), 'testing the accessing of the expected param for a given alias/controller');
    }

    /**
     * Testing the registerFilter method with a valid filter object
     *
     * @since 1.0
     */
    public function testRegisterFilterGood()
    {
        try {
            $_SERVER['REQUEST_URI'] = '/';
            $front = new FrontController();
            $front->registerFilter(new ClientBlacklistFilter());

            $found = false;

            foreach ($front->getFilters() as $filter) {
                if ($filter instanceof ClientBlacklistFilter)
                    $found = true;
            }
            $this->assertTrue($found, 'testing the registerFilter method with a valid filter object');
        } catch (IllegalArguementException $e) {
            $this->fail('testing the registerFilter method with a valid filter object');
        }
    }

    /**
     * Testing the registerFilter method with a bad filter object
     *
     * @since 1.0
     */
    public function testRegisterFilterBad()
    {
        try {
            $_SERVER['REQUEST_URI'] = '/';
            $front = new FrontController();
            $front->registerFilter(new FrontController());

            $this->fail('testing the registerFilter method with a bad filter object');
        }   catch (IllegalArguementException $e) {
            $this->assertEquals('Supplied filter object is not a valid FilterInterface instance!', $e->getMessage(), 'testing the registerFilter method with a bad filter object');
        }
    }

    /**
     * Testing the generateSecureURL method
     *
     * @since 1.2.1
     */
    public function testGenerateSecureURL()
    {
        $config = ConfigProvider::getInstance();

        $oldKey = $config->get('security.encryption.key');
        $oldRewriteSetting = $config->get('app.use.mod.rewrite');

        $config->set('security.encryption.key', 'testkey');
        $params = 'act=ViewArticleTitle&title=Test_Title';

        $config->set('app.use.mod.rewrite', true);
        $this->assertEquals($config->get('app.url').'tk/8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==', FrontController::generateSecureURL($params), 'Testing the generateSecureURL() returns the correct URL with mod_rewrite style URLs enabled');

        $config->set('app.use.mod.rewrite', false);
        $this->assertEquals($config->get('app.url').'?tk=8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==', FrontController::generateSecureURL($params), 'Testing the generateSecureURL() returns the correct URL with mod_rewrite style URLs disabled');

        $config->set('security.encryption.key', $oldKey);
        $config->set('app.use.mod.rewrite', $oldRewriteSetting);
    }
}

?>