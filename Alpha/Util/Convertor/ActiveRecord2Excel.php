<?php

namespace Alpha\Util\Convertor;

use Alpha\Util\Logging\Logger;

/**
 * Class for converting a an active record to an Excel spreadsheet.
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
class ActiveRecord2Excel
{
    /**
     * The record we will convert to an Excel sheet.
     *
     * @var \Alpha\Model\ActiveRecord
     *
     * @since 1.0
     */
    private $BO;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @param \Alpha\Model\ActiveRecord $BO
     *
     * @since 1.0
     */
    public function __construct($BO)
    {
        self::$logger = new Logger('ActiveRecord2Excel');
        self::$logger->debug('>>__construct(BO=['.var_export($BO, true).'])');

        $this->BO = $BO;

        self::$logger->debug('<<__construct');
    }

    /**
     * Returns the output as an Excel spreadsheet.
     *
     * @param bool $renderHeaders Set to false to supress headers in the spreadsheet (defaults to true).
     *
     * @return string
     *
     * @since 1.0
     */
    public function render($renderHeaders = true)
    {
        self::$logger->debug('>>render()');

        //define separator (tabbed character)
        $sep = "\t";

        $output = '';

        // get the class attributes
        $reflection = new \ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        // print headers
        if ($renderHeaders) {
            $output .= $this->BO->getDataLabel('OID').$sep;
            foreach ($properties as $propObj) {
                $propName = $propObj->name;
                if (!in_array($propName, $this->BO->getTransientAttributes()) && !in_array($propName, $this->BO->getDefaultAttributes())) {
                    $output .= $this->BO->getDataLabel($propName).$sep;
                }
            }

            $output .= "\n";
        }

        // print values
        $output .= $this->BO->getOID().$sep;
        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            $prop = $this->BO->getPropObject($propName);
            if (!in_array($propName, $this->BO->getTransientAttributes()) && !in_array($propName, $this->BO->getDefaultAttributes())) {
                if (get_class($prop) == 'DEnum') {
                    $output .= $prop->getDisplayValue().$sep;
                } elseif (get_class($prop) == 'Relation') {
                    $output .= $prop->getRelatedClassDisplayFieldValue().$sep;
                } else {
                    $output .= preg_replace("/[\n\r]/", '', $prop->getValue()).$sep;
                }
            }
        }

        $output .= "\n";

        self::$logger->debug('<<render');

        return $output;
    }
}
