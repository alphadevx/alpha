<?php

namespace Alpha\Test\Model;

use Alpha\Model\Article;
use Alpha\Util\Config\Configprovider;
use Alpha\Exception\AlphaException;

/**
 * Test case for the Article class.
 *
 * @since 3.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
 */
class ArticleTest extends ModelTestCase
{
    /**
     * Testing article title is honouring the cms.url.title.separator config setting
     *
     * @since 3.0
     */
    public function testSetTitle()
    {
        $config = ConfigProvider::getInstance();
        $config->set('cms.url.title.separator', '_');

        $article = new Article();
        $article->set('title', 'Good title');

        $this->assertEquals('Good title', $article->get('title'));

        try {
            $article->set('title', 'Bad_title');
            $this->fail('Setting the title did not throw the expected validation exception!');
        } catch (AlphaException $e) {
            $this->assertEquals('Alpha\Exception\IllegalArguementException', get_class($e));
            $this->assertEquals('Please provide a title for the article. Note that the _ character is not allowed!', $e->getMessage());
        }
    }
}
