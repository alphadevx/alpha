<?php

namespace Alpha\Test\View\Widget;

use Alpha\View\Widget\RecordSelector;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Model\Type\Relation;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Test\Model\ModelTestCase;
use Alpha\Model\Article;
use Alpha\Model\ArticleComment;
use Alpha\Model\Tag;

/**
 * Test case for the RecordSelector widget.
 *
 * @since 3.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
class RecordSelectorTest extends ModelTestCase
{
    /**
     * Set up the tests
     *
     * @since 3.0
     */
    protected function setUp()
    {
        parent::setUp();

        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

        foreach ($this->getActiveRecordProviders() as $provider) {
            $config->set('db.provider.name', $provider[0]);

            //$rights = new Rights();
            //$rights->rebuildTable();

            //$standardGroup = new Rights();
            //$standardGroup->set('name', 'Standard');
            //$standardGroup->save();

            //$request = new BadRequest();
            //$request->rebuildTable();

            //$this->person = $this->createPersonObject('unitTestUser');
            //$this->person->rebuildTable();

            //$lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Rights');

            // just making sure no previous test user is in the DB
            //$this->person->deleteAllByAttribute('URL', 'http://unitTestUser/');
            //$this->person->deleteAllByAttribute('username', 'unitTestUser');

            $article = new Article();
            $article->rebuildTable();
            $comment = new ArticleComment();
            $comment->rebuildTable();
            $tag = new Tag();
            $tag->rebuildTable();
        }
    }

    /**
     * Testing the render() method.
     *
     * @since 3.0
     */
    public function testRender()
    {
        $article = new Article();
        $article->set('title', 'unit test');
        $article->set('description', 'unit test');
        $article->set('content', 'unit test');
        $article->set('author', 'unit test');
        $article->save();

        $comment1 = new ArticleComment();
        $comment1->set('content', 'unit test');
        $comment1->getPropObject('articleID')->setValue($article->getID());
        $comment1->save();

        $comment2 = new ArticleComment();
        $comment2->set('content', 'unit test');
        $comment2->getPropObject('articleID')->setValue($article->getID());
        $comment2->save();

        $relation = new Relation();

        $relation->setRelatedClass('Alpha\Model\ArticleComment');
        $relation->setRelatedClassField('articleID');
        $relation->setRelatedClassDisplayField('content');
        $relation->setRelationType('MANY-TO-ONE');
        $relation->setValue(1);

        $recSelector = new RecordSelector($relation, 'Test label', 'hiddenfield', 'Alpha\Model\ArticleComment');
        $html = $recSelector->render();

        $this->assertTrue(strpos($html, 'http://testapp/recordselector/12m/\'+document.getElementById(\'hiddenfield\').value+\'/hiddenfield/Alpha%5CModel%5CArticleComment/articleID/content') !== false, 'Testing the render() method');

        $relation = new Relation();

        $relation->setRelatedClass('Alpha\Model\ArticleComment');
        $relation->setRelatedClassField('articleID');
        $relation->setRelatedClassDisplayField('content');
        $relation->setRelationType('ONE-TO-MANY');
        $relation->setValue(1);

        $recSelector = new RecordSelector($relation, 'Test label', 'hiddenfield', 'Alpha\Model\Article');
        $html = $recSelector->render();

        $this->assertTrue(strpos($html, 'http://testapp/record/Alpha%5CModel%5CArticleComment/00000000001') !== false, 'Testing the render() method');

        $relation = new Relation();

        $relation->setRelatedClass('Alpha\Model\Article');
        $relation->setRelatedClassField('ID');
        $relation->setRelatedClassDisplayField('content');
        $relation->setRelationType('ONE-TO-MANY');
        $relation->setValue(1);

        $recSelector = new RecordSelector($relation, 'Test label', 'hiddenfield', 'Alpha\Model\Article');
        $html = $recSelector->render();

        $this->assertTrue(strpos($html, 'http://testapp/Alpha%5CController%5CArticleController/ActiveRecordID/00000000001') !== false, 'Testing the render() method');
    }
}
