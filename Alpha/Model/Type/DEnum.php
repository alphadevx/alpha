<?php

namespace Alpha\Model\Type;

use Alpha\Model\ActiveRecord;
use Alpha\Model\ActiveRecordProviderFactory;
use Alpha\Model\Type\SmallText;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;

/**
 * The DEnum (Dynamic Enum) complex data type.  Similiar to Enum,
 * except list items are stored in a database table and are editable.
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
class DEnum extends ActiveRecord implements TypeInterface
{
    /**
     * An array of valid DEnum options.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $options = array();

    /**
     * The currently selected DEnum option.
     *
     * @var int
     *
     * @since 1.0
     */
    protected $value;

    /**
     * The name of the DEnum used in the database.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    protected $name;

    /**
     * The name of the database table for the class.
     *
     * @var string
     *
     * @since 1.0
     */
    const TABLE_NAME = 'DEnum';

    /**
     * An array of data display labels for the class properties.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $dataLabels = array('OID' => 'DEnum ID#', 'name' => 'Name');

    /**
     * The message to display to the user when validation fails.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $helper = 'Not a valid denum option!';

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.2
     */
    private static $logger = null;

    /**
     * Constructor that sets up the DEnum options.
     *
     * @param \Alpha\Model\Type\SmallText $name
     */
    public function __construct($name = null)
    {
        self::$logger = new Logger('DEnum');

        // ensure to call the parent constructor
        parent::__construct();

        $this->markTransient('options');
        $this->markTransient('value');
        $this->markTransient('helper');

        $this->name = new SmallText($name);

        if (isset($name) && $this->checkTableExists()) {
            try {
                $this->loadByAttribute('name', $name);
            } catch (RecordNotFoundException $e) {
                // DEnum does not exist so create it
                $this->save();
            }

            try {
                $this->getOptions();
            } catch (AlphaException $e) {
                self::$logger->warn($e->getMessage());
            }
        }
    }

    /**
     * Setter for the name of the DEnum used in the database.
     *
     * @param string $name
     *
     * @since 1.0
     */
    public function setName($name)
    {
        $this->name->setValue($name);
    }

    /**
     * Get the array of DEnum options from the database.
     *
     * @param bool $alphaSort
     *
     * @return array
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getOptions($alphaSort = false)
    {
        try {
            $options = new self();
            $options->loadByAttribute('name', $this->name->getValue());
        } catch (RecordNotFoundException $e) {
            throw new AlphaException('Failed to load DEnum '.$this->name->getValue().', not found in database.');
        }

        // now build an array of item indexes to be returned
        $count = 0;
        $this->options = array();

        $tmp = new DEnumItem();

        foreach ($tmp->loadItems($options->getOID()) as $DEnumItem) {
            $this->options[$DEnumItem->getID()] = $DEnumItem->getValue();
            ++$count;
        }

        if ($alphaSort) {
            asort($this->options, SORT_STRING);
        }

        return $this->options;
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
     * Getter for the name.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Used to get the current DEnum item selected index value.
     *
     * @return int
     *
     * @since 1.0
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Used to get the current DEnum item string value.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getDisplayValue()
    {
        // check to see if the options have already been loaded from the DB
        if (empty($this->options)) {
            $this->getOptions();
        }

        $val = Integer::zeroPad($this->value);
        if (isset($this->options[$val])) {
            return $this->options[$val];
        } else {
            return 'Unknown';
        }
    }

    /**
     * Used to select the current DEnum item.
     *
     * @param string $item
     *
     * @since 1.0
     */
    public function setValue($item)
    {
        // check to see if the options have already been loaded from the DB
        if (empty($this->options)) {
            $this->getOptions();
        }

        // confirm that the item ID provided is a valid key for the options array
        if (in_array($item, array_keys($this->options))) {
            $this->value = $item;
        } else {
            throw new IllegalArguementException($this->getHelper());
        }
    }

    /**
     * Gets the count from the database of the DEnumItems associated with this object.
     *
     * @return int
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getItemCount()
    {
        $config = ConfigProvider::getInstance();

        $provider = ActiveRecordProviderFactory::getInstance($config->get('db.provider.name'), $this);

        $sqlQuery = 'SELECT COUNT(OID) AS item_count FROM DEnumItem WHERE DEnumID = \''.$this->getID().'\';';

        $this->setLastQuery($sqlQuery);

        $result = $provider->query($sqlQuery);

        if (count($result) > 0 && isset($result[0]['item_count'])) {
            return $result[0]['item_count'];
        } else {
            throw new AlphaException('Failed to get the item count for the DEnum. Database error string is ['.$provider->getLastDatabaseError().']');
        }
    }

    /**
     * Used to get the DenumItem ID for the given option name.
     *
     * @param string $optionName
     *
     * @return int
     *
     * @since 1.0
     */
    public function getOptionID($optionName)
    {
        $denumItem = new DEnumItem();
        $denumItem->loadByAttribute('value', $optionName);
        $id = $denumItem->getID();

        if (!empty($id)) {
            return $id;
        } else {
            return 0;
        }
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
