<?php

namespace Alpha\Model\Type;

use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\ActiveRecord;
use Alpha\Exception\FailedLookupCreateException;
use ReflectionClass;

/**
 * The Relation complex data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
class Relation extends Type implements TypeInterface
{
    /**
     * The name of the business object class which this class is related to.
     *
     * @var string
     *
     * @since 1.0
     */
    private $relatedClass;

    /**
     * The name of the fields of the business object class by which this class is related by.
     *
     * @var string
     *
     * @since 1.0
     */
    private $relatedClassField;

    /**
     * The name of the field from the related business object class which is displayed by the selection widget.
     *
     * @var string
     *
     * @since 1.0
     */
    private $relatedClassDisplayField;

    /**
     * An array of fields to use the values of while rendering related display values via the selection widget.
     *
     * @var array
     *
     * @since 1.0
     */
    private $relatedClassHeaderFields = array();

    /**
     * The name of the business object class on the left of a MANY-TO-MANY relation.
     *
     * @var string
     *
     * @since 1.0
     */
    private $relatedClassLeft;

    /**
     * The name of the field from the related business object class on the left of a
     * MANY-TO-MANY relation which is displayed by the selection widget.
     *
     * @var string
     *
     * @since 1.0
     */
    private $relatedClassLeftDisplayField;

    /**
     * The name of the business object class on the right of a MANY-TO-MANY relation.
     *
     * @var string
     *
     * @since 1.0
     */
    private $relatedClassRight;

    /**
     * The name of the field from the related business object class on the right of a
     * MANY-TO-MANY relation which is displayed by the selection widget.
     *
     * @var string
     *
     * @since 1.0
     */
    private $relatedClassRightDisplayField;

    /**
     * The type of relation ('MANY-TO-ONE' or 'ONE-TO-MANY' or 'ONE-TO-ONE' or 'MANY-TO-MANY').
     *
     * @var string
     *
     * @since 1.0
     */
    private $relationType;

    /**
     * In the case of MANY-TO-MANY relationship, a lookup object will be required.
     *
     * @var \Alpha\Model\Type\RelationLookup
     *
     * @since 1.0
     */
    private $lookup;

    /**
     * In the case of MANY-TO-MANY relationship, this transient array can be used to hold the IDs of related records.
     *
     * @var array
     *
     * @since 2.0
     */
    private $IDs = array();

    /**
     * When building a relation with the TagObject record, set this to the name of the tagged class.
     *
     * @var string
     *
     * @since 1.0
     */
    private $taggedClass;

    /**
     * An array of the allowable relationship types ('MANY-TO-ONE' or 'ONE-TO-MANY' or 'ONE-TO-ONE' or 'MANY-TO-MANY').
     *
     * @var array
     *
     * @since 1.0
     */
    private $allowableRelationTypes = array('MANY-TO-ONE', 'ONE-TO-MANY', 'ONE-TO-ONE', 'MANY-TO-MANY');

    /**
     * The ID value of the related object.  In the special case of a MANY-TO-MANY
     * relation, contains the ID of the object on the current, accessing side.  Can contain NULL.
     *
     * @var mixed
     *
     * @since 1.0
     */
    private $value = null;

    /**
     * The validation rule for the Relation type.
     *
     * @var string
     *
     * @since 1.0
     */
    private $validationRule;

    /**
     * The error message for the Relation type when validation fails.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $helper;

    /**
     * The size of the value for the this Relation.
     *
     * @var int
     *
     * @since 1.0
     */
    private $size = 11;

    /**
     * The absolute maximum size of the value for the this Relation.
     *
     * @var int
     *
     * @since 1.0
     */
    public const MAX_SIZE = 11;

    /**
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        $this->validationRule = Validator::REQUIRED_INTEGER;
        $this->helper = ' not a valid Relation value!  A maximum of '.$this->size.' characters is allowed.';
    }

    /**
     * Set the name of the business object class that this class is related to.
     *
     * @param string $RC
     * @param string $side Only required for MANY-TO-MANY relations
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setRelatedClass(string $RC, string $side = ''): void
    {
        if (in_array($RC, ActiveRecord::getRecordClassNames(), true)) {
            switch ($side) {
                case '':
                    $this->relatedClass = $RC;
                break;
                case 'left':
                    $this->relatedClassLeft = $RC;
                break;
                case 'right':
                    $this->relatedClassRight = $RC;
                break;
                default:
                    throw new IllegalArguementException('The side parameter ['.$RC.'] is not valid!');
            }
        } else {
            throw new IllegalArguementException('The class ['.$RC.'] is not defined anywhere!');
        }
    }

    /**
     * Get the name of the business object class that this class is related to.
     *
     * @param string $side
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function getRelatedClass(string $side = ''): string
    {
        switch ($side) {
            case '':
                return $this->relatedClass;
            break;
            case 'left':
                return $this->relatedClassLeft;
            break;
            case 'right':
                return $this->relatedClassRight;
            break;
            default:
                throw new IllegalArguementException('The side parameter ['.$side.'] is not valid!');
        }
    }

    /**
     * Setter for the field of the related class.
     *
     * @param string $RCF
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setRelatedClassField(string $RCF): void
    {
        // use reflection to sure the related class has the field $RCF
        $reflection = new ReflectionClass($this->relatedClass);
        $properties = $reflection->getProperties();
        $fieldFound = false;

        foreach ($properties as $propObj) {
            if ($RCF == $propObj->name) {
                $fieldFound = true;
                break;
            }
        }

        if ($fieldFound) {
            $this->relatedClassField = $RCF;
        } else {
            throw new IllegalArguementException('The field ['.$RCF.'] was not found in the class ['.$this->relatedClass.']');
        }
    }

    /**
     * Getter for the field of the related class.
     *
     * @since 1.0
     */
    public function getRelatedClassField(): string
    {
        return $this->relatedClassField;
    }

    /**
     * Setter for ONE-TO-MANY relations, which sets the header fields to
     * render from the related class.
     *
     * @param array $fieldNames
     *
     * @since 1.0
     */
    public function setRelatedClassHeaderFields(array $fieldNames): void
    {
        $this->relatedClassHeaderFields = $fieldNames;
    }

    /**
     * Getter for the selection widget field headings of the related class.
     *
     * @since 1.0
     */
    public function getRelatedClassHeaderFields(): array
    {
        return $this->relatedClassHeaderFields;
    }

    /**
     * Setter for the display field from the related class.
     *
     * @param string $RCDF
     * @param string $side Only required for MANY-TO-MANY relations
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setRelatedClassDisplayField(string $RCDF, string $side = ''): void
    {
        switch ($side) {
            case '':
                $this->relatedClassDisplayField = $RCDF;
            break;
            case 'left':
                $this->relatedClassLeftDisplayField = $RCDF;
            break;
            case 'right':
                $this->relatedClassRightDisplayField = $RCDF;
            break;
            default:
                throw new IllegalArguementException('The side paramter ['.$RC.'] is not valid!');
        }
    }

    /**
     * Getter for the display field from the related class.
     *
     * @param string $side Only required for MANY-TO-MANY relations
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function getRelatedClassDisplayField(string $side = ''): string
    {
        switch ($side) {
            case '':
                return $this->relatedClassDisplayField;
            break;
            case 'left':
                return $this->relatedClassLeftDisplayField;
            break;
            case 'right':
                return $this->relatedClassRightDisplayField;
            break;
            default:
                throw new IllegalArguementException('The side paramter ['.$RC.'] is not valid!');
        }
    }

    /**
     * Setter for the relation type.
     *
     * @param string $RT
     *
     * @throws \Alpha\Exception\IllegalArguementException
     * @throws \Alpha\Exception\FailedLookupCreateException
     *
     * @since 1.0
     */
    public function setRelationType(string $RT): void
    {
        if (in_array($RT, $this->allowableRelationTypes, true)) {
            $this->relationType = $RT;
            if ($RT == 'MANY-TO-MANY') {
                try {
                    $this->lookup = new RelationLookup($this->relatedClassLeft, $this->relatedClassRight);
                } catch (FailedLookupCreateException $flce) {
                    throw $flce;
                } catch (IllegalArguementException $iae) {
                    throw $iae;
                }
            }
        } else {
            throw new IllegalArguementException('Relation type of ['.$RT.'] is invalid!');
        }
    }

    /**
     * Getter for the relation type.
     *
     * @since 1.0
     */
    public function getRelationType(): string
    {
        return $this->relationType;
    }

    /**
     * Setter for the value ID of this relation.
     *
     * @param mixed $val
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setValue(mixed $val): void
    {
        if (empty($val)) {
            $this->value = null;
        } else {
            if (!Validator::isInteger($val)) {
                throw new IllegalArguementException("[$val]".$this->helper);
            }

            if (mb_strlen($val) <= $this->size) {
                $this->value = str_pad($val, 11, '0', STR_PAD_LEFT);
            } else {
                throw new IllegalArguementException("[$val]".$this->helper);
            }
        }
    }

    /**
     * Getter for the array of IDs used by MANY-TO-MANY instances.
     *
     * @since 2.0
     */
    public function getRelatedIDs(): array
    {
        return $this->IDs;
    }

    /**
     * Setter for the array of IDs used by MANY-TO-MANY instances.
     *
     * @param array $IDs
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setRelatedIDs(array $IDs): void
    {
        if (is_array($IDs)) {
            $this->IDs = $IDs;
        } else {
            throw new IllegalArguementException('An array must be provided to setRelatedIDs()!');
        }
    }

    /**
     * Getter for the Relation value.
     *
     * @since 1.0
     */
    public function getValue(): string|null
    {
        return $this->value;
    }

    /**
     * Get the validation rule.
     *
     * @since 1.0
     */
    public function getRule(): string
    {
        return $this->validationRule;
    }

    /**
     * Setter to override the default validation rule.
     *
     * @param string $rule
     *
     * @since 1.0
     */
    public function setRule(string $rule): void
    {
        $this->validationRule = $rule;
    }

    /**
     * Getter for the display value of the related class field.  In the case of a
     * MANY-TO-MANY Relation, a comma-seperated sorted list of values is returned.
     *
     * @param string $accessingClassName Used to indicate the reading side when accessing from MANY-TO-MANY relation (leave blank for other relation types)
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function getRelatedClassDisplayFieldValue(string $accessingClassName = ''): string
    {
        if ($this->relationType == 'MANY-TO-MANY') {
            /*
             * 1. Use RelationLookup to get IDs of related objects
             * 2. Load related objects
             * 3. Access the value of the field on the object to build the
             * comma-seperated list.
             */
            if (empty($this->lookup)) {
                throw new IllegalArguementException('Tried to load related MANY-TO-MANY fields but no RelationLookup set on the Relation object!');
            }

            if (empty($accessingClassName)) {
                throw new IllegalArguementException('Tried to load related MANY-TO-MANY fields but no accessingClassName parameter set on the call to getRelatedClassDisplayFieldValue!');
            }

            // load objects on the right from accessing on the left
            if ($accessingClassName == $this->relatedClassLeft) {
                $obj = new $this->relatedClassRight();

                $lookupObjects = $this->lookup->loadAllByAttribute('leftID', $this->value);

                $values = array();
                foreach ($lookupObjects as $lookupObject) {
                    $obj->load($lookupObject->get('rightID'));
                    array_push($values, $obj->get($this->relatedClassRightDisplayField));
                }
                // sort array, then return as comma-seperated string
                asort($values);

                return implode(',', $values);
            }
            // load objects on the left from accessing on the right
            if ($accessingClassName == $this->relatedClassRight) {
                $obj = new $this->relatedClassLeft();

                $lookupObjects = $this->lookup->loadAllByAttribute('rightID', $this->value);

                $values = array();
                foreach ($lookupObjects as $lookupObject) {
                    $obj->load($lookupObject->get('leftID'));
                    array_push($values, $obj->get($this->relatedClassLeftDisplayField));
                }
                // sort array, then return as comma-seperated string
                asort($values);

                return implode(',', $values);
            }
        } else {
            $obj = new $this->relatedClass();
            // making sure we have an object to load
            if (empty($this->value) || $this->value == '00000000000') {
                return '';
            } else {
                $obj->load($this->value);

                return $obj->get($this->relatedClassDisplayField);
            }
        }
    }

    /**
     * For one-to-many and many-to-many relations, get an array of records on the other side.  For one-to-one
     * relations, get the record (Alpha\Model\ActiveRecord instance) on the other side.
     *
     * string $accessingClassName Used to indicate the reading side when accessing from MANY-TO-MANY relation (leave blank for other relation types)
     *
     * @since 3.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function getRelated($accessingClassName = ''): \Alpha\Model\ActiveRecord|array
    {
        if ($this->relationType == 'ONE-TO-ONE') {
            if (!class_exists($this->relatedClass)) {
                throw new IllegalArguementException('Could not load the definition for the ActiveRecord class ['.$this->relatedClass.']');
            }

            $record = new $this->relatedClass();
            $record->loadByAttribute($this->getRelatedClassField(), $this->getValue());

            return $record;
        }

        if ($this->relationType == 'ONE-TO-MANY') {
            if ($this->getValue() == '') { // if the value is empty, then return an empty array
                return array();
            }

            $record = new $this->relatedClass();
            if ($this->relatedClass == 'Alpha\Model\Tag') {
                $records = $record->loadTags($this->taggedClass, $this->getValue());
            } else {
                $records = $record->loadAllByAttribute($this->getRelatedClassField(), $this->getValue());
            }

            return $records;
        } else { // MANY-TO-MANY
            if (empty($this->lookup)) {
                throw new IllegalArguementException('Tried to load related MANY-TO-MANY objects but no RelationLookup set on the Relation object!');
            }

            if (empty($accessingClassName)) {
                throw new IllegalArguementException('Tried to load related MANY-TO-MANY objects but no accessingClassName parameter set on the call to getRelated!');
            }

            $records = array();

            // load objects on the right from accessing on the left
            if ($accessingClassName == $this->relatedClassLeft) {
                $lookups = $this->lookup->loadAllByAttribute('leftID', $this->value);

                foreach ($lookups as $lookup) {
                    $record = new $this->relatedClassRight();
                    $record->load($lookup->get('rightID'));
                    array_push($records, $record);
                }
            }
            // load objects on the left from accessing on the right
            if ($accessingClassName == $this->relatedClassRight && count($records) == 0) {
                $lookups = $this->lookup->loadAllByAttribute('rightID', $this->value);

                foreach ($lookups as $lookup) {
                    $record = new $this->relatedClassLeft();
                    $record->load($lookup->get('leftID'));
                    array_push($records, $record);
                }
            }

            return $records;
        }
    }

    /**
     * Get the allowable size of the Relation in the database field.
     *
     * @since 1.0
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get the lookup object if available (only on MANY-TO-MANY relations, null otherwise).
     *
     * @since 1.0
     */
    public function getLookup(): \Alpha\Model\Type\RelationLookup
    {
        return $this->lookup;
    }

    /**
     * Gets the side ('left' or 'right') of the passed classname on the current Relation object.
     *
     * @param string $RecordClassname
     *
     * @since 1.0
     *
     * @throws \Alpha\Model\Type\IllegalArguementException
     */
    public function getSide(string $RecordClassname): string
    {
        if ($RecordClassname == $this->relatedClassLeft) {
            return 'left';
        } elseif ($RecordClassname == $this->relatedClassRight) {
            return 'right';
        } else {
            throw new IllegalArguementException('Error trying to determine the MANY-TO-MANY relationship side for the classname ['.$RecordClassname.']');
        }
    }

    /**
     * Set the taggedClass property to the name of the tagged class when building relations
     * to the TagObject record.
     *
     * @param string $taggedClass
     *
     * @since 1.0
     */
    public function setTaggedClass(string $taggedClass): void
    {
        $this->taggedClass = $taggedClass;
    }
}
