<?php

namespace Alpha\Model\Type;

use Alpha\Model\ActiveRecord;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Exception\FailedLookupCreateException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\AlphaException;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use ReflectionClass;

/**
 * The RelationLookup complex data type.  Used to store object2object lookup tables for
 * MANY-TO-MANY relationships between record objects.
 *
 * @since 1.0
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
class RelationLookup extends ActiveRecord implements TypeInterface
{
    /**
     * The ID of the left business object in the relation.
     *
     * @var \Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    protected $leftID;

    /**
     * The ID of the right business object in the relation.
     *
     * @var \Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    protected $rightID;

    /**
     * The name of the left business object class in the relation.
     *
     * @var string
     *
     * @since 1.0
     */
    private $leftClassName;

    /**
     * The name of the right business object class in the relation.
     *
     * @var string
     *
     * @since 1.0
     */
    private $rightClassName;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * an array of data display labels for the class properties.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $dataLabels = array('ID' => 'RelationLookup ID#', 'leftID' => 'Left Record ID#', 'rightID' => 'Right Record ID#');

    /**
     * The message to display to the user when validation fails.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $helper = 'Not a valid RelationLookup value!';

    /**
     * The constructor.
     *
     * @throws \Alpha\Exception\FailedLookupCreateException
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function __construct($leftClassName, $rightClassName)
    {
        self::$logger = new Logger('RelationLookup');
        self::$logger->debug('>>__construct(leftClassName=['.$leftClassName.'], rightClassName=['.$rightClassName.'])');

        // ensure to call the parent constructor
        parent::__construct();

        if (empty($leftClassName) || empty($rightClassName)) {
            throw new IllegalArguementException('Cannot create RelationLookup object without providing the left and right class names!');
        }

        $this->leftClassName = $leftClassName;
        $this->rightClassName = $rightClassName;

        $this->leftID = new Integer();
        $this->rightID = new Integer();

        $this->markTransient('leftClassName');
        $this->markTransient('rightClassName');
        $this->markTransient('helper');
        $this->markTransient('TABLE_NAME');

        // add a unique composite key to these fields
        $this->markUnique('leftID', 'rightID');

        // make sure the lookup table exists
        if (!$this->checkTableExists() && ActiveRecord::isInstalled()) {
            // first make sure that the two Record tables exist before relating them with a lookup table
            if (ActiveRecord::checkRecordTableExists($leftClassName) && ActiveRecord::checkRecordTableExists($rightClassName)) {
                $this->makeTable();
            } else {
                throw new FailedLookupCreateException('Error trying to create a lookup table ['.$this->getTableName().'], as tables for records ['.$leftClassName.'] or ['.$rightClassName.'] don\'t exist!');
            }
        }

        self::$logger->debug('<<__construct');
    }

    /**
     * Get the leftClassName value.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getLeftClassName()
    {
        return $this->leftClassName;
    }

    /**
     * Get the rightClassName value.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getRightClassName()
    {
        return $this->rightClassName;
    }

    /**
     * Custom getter for the TABLE_NAME, which can't be static in this class due to
     * the lookup tablenames being different each time.
     *
     * @return string
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getTableName()
    {
        if (isset($this->leftClassName) && isset($this->rightClassName)) {
            $leftClass = new ReflectionClass($this->leftClassName);
            $left = $leftClass->getShortname();
            $rightClass = new ReflectionClass($this->rightClassName);
            $right = $rightClass->getShortname();
            self::$logger->debug('Setting table name to ['.$left.'2'.$right.']');

            return $left.'2'.$right;
        } else {
            throw new AlphaException('No table name set for the class ['.get_class($this).'], left or right class name(s) missing');
        }
    }

    /**
     * This custom version provides the left/right class names to the business object constructor, required
     * for RelationLookup objects.
     *
     * (non-PHPdoc)
     *
     * @see Alpha\Model\ActiveRecord::loadAllByAttribute()
     * @param string $attribute
     */
    public function loadAllByAttribute($attribute, $value, $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false, $constructorArgs = array())
    {
        if (!isset(self::$logger)) {
            self::$logger = new Logger('RelationLookup');
        }

        self::$logger->debug('>>loadAllByAttribute(attribute=['.$attribute.'], value=['.$value.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.'], constructorArgs=['.print_r($constructorArgs, true).']');

        if (method_exists($this, 'before_loadAllByAttribute_callback')) {
            $this->{'before_loadAllByAttribute_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $objects = $provider->loadAllByAttribute($attribute, $value, $start, $limit, $orderBy, $order, $ignoreClassType, array($this->leftClassName, $this->rightClassName));

        if (method_exists($this, 'after_loadAllByAttribute_callback')) {
            $this->{'after_loadAllByAttribute_callback'}();
        }

        self::$logger->debug('<<loadAllByAttribute ['.count($objects).']');

        return $objects;
    }

    /**
     * This custom version provides the left/right class names to the business object constructor, required
     * for RelationLookup objects.
     *
     * (non-PHPdoc)
     *
     * @see Alpha\Model\ActiveRecord::loadAllByAttributes()
     */
    public function loadAllByAttributes($attributes = array(), $values = array(), $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false)
    {
        self::$logger->debug('>>loadAllByAttributes(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'], start=['.
            $start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        if (method_exists($this, 'before_loadAllByAttributes_callback')) {
            $this->{'before_loadAllByAttributes_callback'}();
        }

        $config = ConfigProvider::getInstance();

        if (!is_array($attributes) || !is_array($values)) {
            throw new IllegalArguementException('Illegal arrays attributes=['.var_export($attributes, true).'] and values=['.var_export($values, true).
                '] provided to loadAllByAttributes');
        }

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $objects = $provider->loadAllByAttributes($attributes, $values, $start, $limit, $orderBy, $order, $ignoreClassType, array($this->leftClassName, $this->rightClassName));

        if (method_exists($this, 'after_loadAllByAttributes_callback')) {
            $this->{'after_loadAllByAttributes_callback'}();
        }

        self::$logger->debug('<<loadAllByAttributes ['.count($objects).']');

        return $objects;
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
     * Returns an array of the IDs of the related objects.
     *
     * @return integer[]
     *
     * @since 1.0
     */
    public function getValue()
    {
        return array($this->leftID->getValue(), $this->rightID->getValue());
    }

    /**
     * Used to set the IDs of the related objects.  Pass a two-item array of IDs, the first
     * one being the left object ID, the second being the right.
     *
     * @param string[] $IDs
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setValue($IDs)
    {
        try {
            $this->leftID->setValue($IDs[0]);
            $this->rightID->setValue($IDs[1]);
        } catch (\Exception $e) {
            throw new IllegalArguementException('Array value passed to setValue is not valid ['.var_export($IDs, true).'], array should contain two IDs');
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
        return strval($this->getTableName());
    }
}
