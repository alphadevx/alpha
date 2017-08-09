<?php

namespace Alpha\Model\Type;

use Alpha\Model\ActiveRecord;
use Alpha\Model\ActiveRecordProviderFactory;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\AlphaException;
use Alpha\Exception\CustomQueryException;

/**
 * The DEnumItem (Dynamic Enum Item) complex data type.  Has a one-to-many
 * relationship with the DEnum type.
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
class DEnumItem extends ActiveRecord implements TypeInterface
{
    /**
     * The value that will appear in the drop-down.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    protected $value;

    /**
     * The ID of the parent DEnum object.
     *
     * @var \Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    protected $DEnumID;

    /**
     * The name of the database table for the class.
     *
     * @var string
     *
     * @since 1.0
     */
    const TABLE_NAME = 'DEnumItem';

    /**
     * an array of data display labels for the class properties.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $dataLabels = array('ID' => 'DEnumItem ID#', 'value' => 'Dropdown value');

    /**
     * The message to display to the user when validation fails.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $helper = 'Not a valid DEnumItem value!';

    /**
     * The constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        // ensure to call the parent constructor
        parent::__construct();

        $this->value = new SmallText();
        $this->value->setRule(Validator::REQUIRED_STRING);
        $this->value->setHelper('A blank dropdown value is not allowed!');
        $this->DEnumID = new Integer();
        $this->markTransient('helper');
    }

    /**
     * Loads all of the items for the given parent DEnum ID.
     *
     * @param int $EnumID The ID of the parent DEnum object.
     *
     * @return array
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function loadItems($EnumID)
    {
        $config = ConfigProvider::getInstance();

        $this->DEnumID->setValue($EnumID);

        $sqlQuery = 'SELECT ID FROM '.self::TABLE_NAME.' WHERE DEnumID = \''.$EnumID.'\';';

        $provider = ActiveRecordProviderFactory::getInstance($config->get('db.provider.name'), $this);

        try {
            $result = $provider->query($sqlQuery);
        } catch (CustomQueryException $e) {
            throw new AlphaException('Failed to load objects, error is ['.$e->getMessage().']');

            return array();
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;

        foreach ($result as $row) {
            $obj = new self();
            $obj->load($row['ID']);
            $objects[$count] = $obj;
            ++$count;
        }

        return $objects;
    }

    /**
     * used to get the current DEnum item.
     *
     * @return \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * used to set the current DEnum item.
     *
     * @param string $item
     *
     * @since 1.0
     */
    public function setValue($item)
    {
        $this->value->setValue($item);
    }

    /**
     * Getter for the validation helper string.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Set the validation helper text.
     *
     * @param string $helper
     *
     * @since 1.0
     */
    public function setHelper($helper)
    {
        $this->helper = $helper;
    }

    /**
     * Used to convert the object to a printable string.
     *
     * @return string
     *
     * @since 1.0
     */
    public function __toString()
    {
        return strval($this->value);
    }
}
