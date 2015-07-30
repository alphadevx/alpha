<?php

namespace Alpha\View;

use Alpha\Controller\Front\FrontController;
use Alpha\Util\Logging\Logger;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\Type\String;
use Alpha\View\Widget\Button;

/**
 *
 * The rendering class for the Sequence class
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
class SequenceView extends View
{
    /**
     * Trace logger
     *
     * @var Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor
     *
     * @param Alpha\Model\ActiveRecord $BO
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    protected function __construct($BO)
    {
        self::$logger = new Logger('SequenceView');
        self::$logger->debug('>>__construct(BO=['.var_export($BO, true).'])');

        parent::__construct($BO);

        self::$logger->debug('<<__construct');
    }

    /**
     * Custom list view
     *
     * @param array $fields Hash array of HTML fields to pass to the template.
     * @since 1.0
     */
    public function listView($fields=array())
    {
        self::$logger->debug('>>listView(fields=['.var_export($fields, true).'])');

        if (method_exists($this, 'before_listView_callback'))
            $this->before_listView_callback();

        $config = ConfigProvider::getInstance();

        // the form action
        $fields['formAction'] = $fields['URI'];

        // work out how many columns will be in the table
        $reflection = new \ReflectionClass(get_class($this->BO));
        $properties = array_keys($reflection->getDefaultProperties());
        $fields['colCount'] = 1+count(array_diff($properties, $this->BO->getDefaultAttributes(), $this->BO->getTransientAttributes()));

        // get the class attributes
        $properties = $reflection->getProperties();

        $html = '';

        $html .= '<tr>';
        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            // skip over password fields
            $property = $this->BO->getPropObject($propName);
            if (!($property instanceof String && $property->checkIsPassword())) {
                if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
                    $html .= '  <th>'.$this->BO->getDataLabel($propName).'</th>';
                }
                if ($propName == 'OID')
                    $html .= '  <th>'.$this->BO->getDataLabel($propName).'</th>';
            } else {
                $fields['colCount'] = $fields['colCount']-1;
            }
        }
        $html .= '</tr><tr>';

        $fields['formHeadings'] = $html;

        $html = '';

        // and now the values
        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            $property = $this->BO->getPropObject($propName);
            if (!($property instanceof String && $property->checkIsPassword())) {
                if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
                    $propClass = get_class($this->BO->getPropObject($propName));

                    if ($propClass == 'Alpha\Model\Type\Text') {
                        $text = htmlentities($this->BO->get($propName), ENT_COMPAT, 'utf-8');
                        if(mb_strlen($text) > 70)
                            $html .= '  <td>&nbsp;'.mb_substr($text, 0, 70).'...</td>';
                        else
                            $html .= '  <td>&nbsp;'.$text.'</td>';
                    }elseif($propClass == 'Alpha\Model\Type\DEnum') {
                        $html .= '  <td>&nbsp;'.$this->BO->getPropObject($propName)->getDisplayValue().'</td>';
                    }else{
                        $html .= '  <td>&nbsp;'.$this->BO->get($propName).'</td>';
                    }
                }
                if ($propName == 'OID')
                    $html .= '  <td>&nbsp;'.$this->BO->getOID().'</td>';
            }
        }
        $html .= '</tr>';

        $fields['formFields'] = $html;

        // View button
        if (strpos($fields['URI'], '/tk/') !== false) {
            $button = new Button("document.location = '".FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getOID())."';", 'View', 'viewBut');
            $fields['viewButton'] = $button->render();
        } else {
            $button = new Button("document.location = '".$this->BO->get('URL')."';", 'View', 'viewBut');
            $fields['viewButton'] = $button->render();
        }

        // supressing the edit/delete buttons for Sequences
        $fields['adminButtons'] = '';

        // buffer security fields to $formSecurityFields variable
        $fields['formSecurityFields'] = $this->renderSecurityFields();

        $this->loadTemplate($this->BO, 'list', $fields);

        if (method_exists($this, 'after_listView_callback'))
            $this->after_listView_callback();

        self::$logger->debug('<<listView');
    }

    /**
     * Custom display view
     *
     * @param array $fields Hash array of HTML fields to pass to the template.
     * @since 1.0
     */
    public function detailedView($fields=array())
    {
        self::$logger->debug('>>detailedView(fields=['.var_export($fields, true).'])');

        if (method_exists($this, 'before_detailedView_callback'))
            $this->before_detailedView_callback();

        $config = ConfigProvider::getInstance();

        // we may want to display the OID regardless of class
        $fields['OIDLabel'] = $this->BO->getDataLabel('OID');
        $fields['OID'] = $this->BO->getOID();

        // buffer form fields to $formFields
        $fields['formFields'] = $this->renderAllFields('view');

        // Back button
        $button = new Button('history.back()', 'Back', 'backBut');
        $fields['backButton'] = $button->render();

        $fields['adminButtons'] = '';

        $this->loadTemplate($this->BO, 'detail', $fields);

        if (method_exists($this, 'after_detailedView_callback'))
            $this->after_detailedView_callback();

        self::$logger->debug('<<detailedView');
    }
}

?>