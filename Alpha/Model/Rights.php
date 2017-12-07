<?php

namespace Alpha\Model;

use Alpha\Model\Type\SmallText;
use Alpha\Model\Type\Relation;
use Alpha\Util\Logging\Logger;

/**
 * The group level rights object for the application permissions.
 *
 * @since 1.0
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
class Rights extends ActiveRecord
{
    /**
     * The name of the rights.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    protected $name;

    /**
     * A Relation containing all of the Person objects that have these rights.
     *
     * @var \Alpha\Model\Type\Relation
     *
     * @since 1.0
     */
    protected $members;

    /**
     * An array of data display labels for the class properties.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $dataLabels = array('ID' => 'Rights Group ID#', 'name' => 'Rights Group Name', 'members' => 'Rights Group Members');

    /**
     * The name of the database table for the class.
     *
     * @var string
     *
     * @since 1.0
     */
    const TABLE_NAME = 'Rights';

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.1
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('Rights');

        // ensure to call the parent constructor
        parent::__construct();
        $this->name = new SmallText();

        // add unique key to name field
        $this->markUnique('name');

        $this->members = new Relation();
        $this->markTransient('members');
        $this->setupRels();
    }

    /**
     * Get the group members Relation.
     *
     * @return \Alpha\Model\Type\Relation
     *
     * @since 1.0
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Set up the transient attributes for the rights group after it has loaded.
     *
     * @since 1.0
     */
    protected function after_load_callback()
    {
        $this->setupRels();
    }

    /**
     * Set up the transient attributes for the rights group after it has been created.
     *
     * @since 1.2.1
     */
    protected function after_save_callback()
    {
        $this->setupRels();
    }

    /**
     * Set up the transient attributes for the rights group after it has loaded.
     *
     * @since 1.0
     */
    protected function after_loadByAttribute_callback()
    {
        $this->setupRels();
    }

    /**
     * Sets up the Relation definitions on this record.
     *
     * @since 1.0
     */
    protected function setupRels()
    {
        // set up MANY-TO-MANY relation person2rights
        $this->members->setRelatedClass('Alpha\Model\Person', 'left');
        $this->members->setRelatedClassDisplayField('email', 'left');
        $this->members->setRelatedClass('Alpha\Model\Rights', 'right');
        $this->members->setRelatedClassDisplayField('name', 'right');
        $this->members->setRelationType('MANY-TO-MANY');
        $this->members->setValue($this->getID());
    }
}
