<?php

namespace Alpha\Test\Util\Config;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Backup\BackupUtils;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Person;
use Alpha\Model\Rights;
use Alpha\Model\BadRequest;
use Alpha\Model\Article;
use Alpha\Model\ArticleComment;
use Alpha\Model\ArticleVote;
use Alpha\Model\Tag;
use Alpha\Model\Type\RelationLookup;
use Alpha\Test\Model\ModelTestCase;

/**
 * Test cases for the BackupUtils class.
 *
 * @since 3.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2019, John Collins (founder of Alpha Framework).
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
class BackupUtilsTest extends ModelTestCase
{
    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 3.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = ConfigProvider::getInstance();

        if (file_exists($config->get('backup.dir').'testbackup.sql')) {
            unlink($config->get('backup.dir').'testbackup.sql');
        }

        foreach ($this->getActiveRecordProviders() as $provider) {
            $config->set('db.provider.name', $provider[0]);

            $rights = new Rights();
            $rights->rebuildTable();

            $standardGroup = new Rights();
            $standardGroup->set('name', 'Standard');
            $standardGroup->save();

            $request = new BadRequest();
            $request->rebuildTable();

            $this->person = $this->createPersonObject('unitTestUser');
            $this->person->rebuildTable();

            $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Rights');

            // just making sure no previous test user is in the DB
            $this->person->deleteAllByAttribute('URL', 'http://unitTestUser/');
            $this->person->deleteAllByAttribute('username', 'unitTestUser');

            $article = new Article();
            $article->rebuildTable();
            $comment = new ArticleComment();
            $comment->rebuildTable();
            $tag = new Tag();
            $tag->rebuildTable();
        }
    }

    /**
     * Creates a person object for Testing.
     *
     * @return \Alpha\Model\Person
     *
     * @since 3.0
     */
    private function createPersonObject($name)
    {
        $person = new Person();
        $person->setUsername($name);
        $person->set('email', $name.'@test.com');
        $person->set('password', 'passwordTest');
        $person->set('URL', 'http://unitTestUser/');

        return $person;
    }

    /**
     * Testing that attempting to access a config value that is not set will cause an exception.
     *
     * @since 3.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testBackUpDatabase($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        BackupUtils::backupDatabase($config->get('backup.dir'));

        $filename = $config->get('backup.dir').$config->get('db.name').'_'.date('Y-m-d').'.sql';

        $this->assertTrue(file_exists($filename));
        $this->assertTrue(filesize($filename) > 0);

        $content = file_get_contents($filename);

        $this->assertTrue(strpos($content, 'Person') !== false);
    }
}
