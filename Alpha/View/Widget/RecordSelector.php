<?php

namespace Alpha\View\Widget;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Util\Http\Request;
use Alpha\Model\Type\Relation;
use Alpha\Exception\IllegalArguementException;
use Alpha\Controller\Controller;
use Alpha\Controller\Front\FrontController;

/**
 * Record selection HTML widget.
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
class RecordSelector
{
    /**
     * The relation object that we are going to render a view for.
     *
     * @var \Alpha\Model\Type\Relation
     *
     * @since 1.0
     */
    private $relationObject = null;

    /**
     * The label text to use where required.
     *
     * @var string
     *
     * @since 1.0
     */
    private $label;

    /**
     * Used to indicate the reading side when accessing from MANY-TO-MANY relation
     * (leave blank for other relation types).
     *
     * @var string
     *
     * @since 1.0
     */
    private $accessingClassName;

    /**
     * Javascript to run when the widget opens in a new window.
     *
     * @var string
     *
     * @since 1.0
     */
    private $onloadJS = '';

    /**
     * The name of the HTML input box for storing the hidden and display values.
     *
     * @var string
     *
     * @since 1.0
     */
    private $name;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * The constructor.
     *
     * @param \Alpha\Model\Type\Relation $relation
     * @param string                    $label
     * @param string                    $name
     * @param string                    $accessingClassName
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function __construct(\Alpha\Model\Type\Relation $relation, string $label = '', string $name = '', string $accessingClassName = '')
    {
        self::$logger = new Logger('RecordSelector');
        self::$logger->debug('>>__construct(relation=['.$relation.'], label=['.$label.'], name=['.$name.'], accessingClassName=['.$accessingClassName.'])');

        if (!$relation instanceof Relation) {
            throw new IllegalArguementException('Invalid Relation object provided to the RecordSelector constructor!');
        }

        $this->relationObject = $relation;
        $this->label = $label;
        $this->name = $name;
        $this->accessingClassName = $accessingClassName;

        self::$logger->debug('<<__construct');
    }

    /**
     * Renders the text boxes and buttons for the widget, that will appear in user forms.
     *
     * @param bool $expanded Render the related fields in expanded format or not (optional)
     * @param bool $buttons  Render buttons for expanding/contacting the related fields (optional)
     *
     * @since 1.0
     */
    public function render(bool $expanded = false, bool $buttons = true): string
    {
        self::$logger->debug('>>render(expanded=['.$expanded.'], buttons=['.$buttons.'])');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt($this->name)) : $this->name);

        $html = '';

        // render text-box for many-to-one relations
        if ($this->relationObject->getRelationType() == 'MANY-TO-ONE') {
            // value to appear in the text-box
            $inputBoxValue = $this->relationObject->getRelatedClassDisplayFieldValue();

            $html .= '<div class="form-group">';
            $html .= '<label for="'.$this->name.'_display">'.$this->label.'</label>';

            $html .= '<input type="text" size="70" class="form-control" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" disabled/>';

            $js = " if(window.jQuery) {
                        window.jQuery.dialog = new BootstrapDialog({
                            title: 'Please select',
                            message: 'Loading...',
                            onshow: function(dialogRef){
                                dialogRef.getModalBody().load('".$config->get('app.url')."/recordselector/12m/'+document.getElementById('".$fieldname."').value+'/".$this->name.'/'.urlencode($this->relationObject->getRelatedClass()).'/'.$this->relationObject->getRelatedClassField().'/'.$this->relationObject->getRelatedClassDisplayField()."');
                            },
                            buttons: [
                            {
                                icon: 'glyphicon glyphicon-remove',
                                label: 'Cancel',
                                cssClass: 'btn btn-default btn-xs',
                                action: function(dialogItself){
                                    dialogItself.close();
                                }
                            }
                        ]
                        });
                        window.jQuery.dialog.open();
                    }";

            $tmp = new Button($js, 'Select', 'relBut', '', 'glyphicon-check');
            $html .= '<div class="centered lower">'.$tmp->render().'</div>';

            // hidden field to store the actual value of the relation
            $html .= '<input type="hidden" name="'.$fieldname.'" id="'.$fieldname.'" value="'.$this->relationObject->getValue().'"/>';

            if ($this->relationObject->getRule() != '') {
                $html .= '<input type="hidden" id="'.$fieldname.'_msg" value="'.$this->relationObject->getHelper().'"/>';
                $html .= '<input type="hidden" id="'.$fieldname.'_rule" value="'.$this->relationObject->getRule().'"/>';
            }

            $html .= '</div>';
        }

        // render read-only list for one-to-many relations
        if ($this->relationObject->getRelationType() == 'ONE-TO-MANY') {
            $objects = $this->relationObject->getRelated();

            if (count($objects) > 0) {
                // render tags differently
                if ($this->name == 'tags' && $this->relationObject->getRelatedClass() == 'TagObject') {
                    $html .= '<p><strong>'.$this->label.':</strong>';

                    foreach ($objects as $tag) {
                        $html .= ' <a href="'.$config->get('app.url').'/search/'.$tag->get('content').'">'.$tag->get('content').'</a>';
                    }

                    $html .= '</p>';
                } else {
                    $html .= '<div><strong>'.$this->label.':</strong>';
                    if ($buttons) {
                        $html .= '<div class="spread">';
                        $tmp = new Button("document.getElementById('relation_field_".$this->name."').style.display = '';", 'Show', $this->name.'DisBut', '', 'glyphicon-list');
                        $html .= $tmp->render();
                        $tmp = new Button("document.getElementById('relation_field_".$this->name."').style.display = 'none';", 'Hide', $this->name.'HidBut', '', 'glyphicon-minus');
                        $html .= $tmp->render();
                        $html .= '</div>';
                    }
                    $html .= '</div>';

                    $html .= '<div id="relation_field_'.$this->name.'" style="display:'.($expanded ? '' : 'none').';">';

                    $customControllerName = Controller::getCustomControllerName(get_class($objects[0]));

                    $request = new Request(array('method' => 'GET'));
                    $URI = $request->getURI();

                    foreach ($objects as $obj) {

                        // check to see if we are in the admin back-end
                        if (mb_strpos($URI, '/tk/') !== false) {
                            $viewURL = FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.get_class($obj).'&ActiveRecordID='.$obj->getID());
                            $editURL = FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.get_class($obj).'&ActiveRecordID='.$obj->getID().'&view=edit');
                        } else {
                            if (isset($customControllerName)) {
                                if ($config->get('app.use.pretty.urls')) {
                                    $viewURL = $config->get('app.url').'/'.urlencode($customControllerName).'/ActiveRecordID/'.$obj->getID();
                                    $editURL = $config->get('app.url').'/'.urlencode($customControllerName).'/ActiveRecordID/'.$obj->getID().'/edit';
                                } else {
                                    $viewURL = $config->get('app.url').'/?act='.urlencode($customControllerName).'&ActiveRecordID='.$obj->getID();
                                    $editURL = $config->get('app.url').'/?act='.urlencode($customControllerName).'&ActiveRecordID='.$obj->getID().'&view=edit';
                                }
                            } else {
                                $viewURL = $config->get('app.url').'/record/'.urlencode(get_class($obj)).'/'.$obj->getID();
                                $editURL = $config->get('app.url').'/record/'.urlencode(get_class($obj)).'/'.$obj->getID().'/edit';
                            }
                        }

                        /*
                         * If any display headers were set with setRelatedClassHeaderFields, use them otherwise
                         * use the ID of the related class as the only header.
                         */
                        $headerFields = $this->relationObject->getRelatedClassHeaderFields();
                        if (count($headerFields) > 0) {
                            foreach ($headerFields as $field) {
                                $label = $obj->getDataLabel($field);
                                $value = $obj->get($field);

                                if ($field == 'created_by' || $field == 'updated_by') {
                                    $person = new PersonObject();
                                    $person->load($value);
                                    $value = $person->getUsername();
                                }

                                $html .= '<em>'.$label.': </em>'.$value.'&nbsp;&nbsp;&nbsp;&nbsp;';
                            }
                            // if the related Record has been updated, render the update time
                            if ($obj->getCreateTS() != $obj->getUpdateTS()) {
                                try {
                                    $html .= '<em>'.$obj->getDataLabel('updated_ts').': </em>'.$obj->get('updated_ts');
                                } catch (IllegalArguementException $e) {
                                    $html .= '<em>Updated: </em>'.$obj->get('updated_ts');
                                }
                            }
                        } else {
                            $html .= '<em>'.$obj->getDataLabel('ID').': </em>'.$obj->get('ID');
                        }
                        // ensures that line returns are rendered
                        $value = str_replace("\n", '<br>', $obj->get($this->relationObject->getRelatedClassDisplayField()));
                        $html .= '<p>'.$value.'</p>';

                        $html .= '<div class="centered">';
                        $html .= '<a href="'.$viewURL.'">View</a>';
                        // if the current user owns it, they get the edit link
                        if ($session->get('currentUser') != null && $session->get('currentUser')->getID() == $obj->getCreatorId()) {
                            $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$editURL.'">Edit</a>';
                        }
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }
            }
        }

        // render text-box for many-to-many relations
        if ($this->relationObject->getRelationType() == 'MANY-TO-MANY') {
            // value to appear in the text-box
            $inputBoxValue = $this->relationObject->getRelatedClassDisplayFieldValue($this->accessingClassName);
            // replace commas with line returns
            $inputBoxValue = str_replace(',', "\n", $inputBoxValue);

            $html .= '<div class="form-group">';
            $html .= '<label for="'.$this->name.'_display">'.$this->label.'</label>';

            $html .= '<textarea id="'.$this->name.'_display" class="form-control" rows="5" readonly>';
            $html .= $inputBoxValue;
            $html .= '</textarea>';

            $fieldname1 = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt($this->name)) : $this->name);
            $fieldname2 = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt($this->name.'_ID')) : $this->name.'_ID');

            $js = "if(window.jQuery) {
                        BootstrapDialog.show({
                            title: 'Please select',
                            message: 'Loading...',
                            onshow: function(dialogRef){
                                dialogRef.getModalBody().load('".$config->get('app.url')."/recordselector/m2m/'+document.getElementById('".$fieldname2."').value+'/".$this->name.'/'.urlencode($this->relationObject->getRelatedClass('left')).'/'.$this->relationObject->getRelatedClassDisplayField('left').'/'.urlencode($this->relationObject->getRelatedClass('right')).'/'.$this->relationObject->getRelatedClassDisplayField('right').'/'.urlencode($this->accessingClassName)."/'+document.getElementById('".$fieldname1."').value);
                            },
                            buttons: [
                            {
                                icon: 'glyphicon glyphicon-remove',
                                label: 'Cancel',
                                cssClass: 'btn btn-default btn-xs',
                                action: function(dialogItself){
                                    dialogItself.close();
                                }
                            },
                            {
                                icon: 'glyphicon glyphicon-ok',
                                label: 'Okay',
                                cssClass: 'btn btn-default btn-xs',
                                action: function(dialogItself) {
                                    setParentFieldValues();
                                    $('[id=\'".$this->name."_display\']').blur();
                                    dialogItself.close();
                                }
                            }
                        ]
                        });
                    }";

            $tmp = new Button($js, 'Select', 'relBut', '', 'glyphicon-check');
            $html .= '<div class="centered lower">'.$tmp->render().'</div>';

            $html .= '</div>';

            // hidden field to store the ID of the current record
            $html .= '<input type="hidden" name="'.$fieldname2.'" id="'.$fieldname2.'" value="'.$this->relationObject->getValue().'"/>';

            // hidden field to store the IDs of the related records on the other side of the rel (this is what we check for when saving)
            if ($this->relationObject->getSide($this->accessingClassName) == 'left') {
                $lookupIDs = $this->relationObject->getLookup()->loadAllFieldValuesByAttribute('leftID', $this->relationObject->getValue(), 'rightID', 'DESC');
            } else {
                $lookupIDs = $this->relationObject->getLookup()->loadAllFieldValuesByAttribute('rightID', $this->relationObject->getValue(), 'leftID', 'DESC');
            }

            $html .= '<input type="hidden" name="'.$fieldname1.'" id="'.$fieldname1.'" value="'.implode(',', $lookupIDs).'"/>';
        }

        self::$logger->debug('<<__render [html]');

        return $html;
    }

    /**
     * Returns the HTML for the record selector that will appear in a pop-up window.
     *
     * @param string $fieldname  The hidden HTML form field in the parent to pass values back to.
     * @param array  $lookupIDs An optional array of related look-up IDs, only required for rendering MANY-TO-MANY rels
     *
     * @since 1.0
     */
    public function renderSelector(string $fieldname, array $lookupIDs = array()): string
    {
        self::$logger->debug('>>renderSelector(fieldname=['.$fieldname.'], lookupIDs=['.var_export($lookupIDs, true).'])');

        $config = ConfigProvider::getInstance();

        $html = '<script language="JavaScript">
            var selectedIDs = new Object();

            function toggelOID(oid, displayValue, isSelected) {
                if(isSelected)
                    selectedIDs[oid] = displayValue;
                else
                    delete selectedIDs[oid];
            }

            function setParentFieldValues() {
                var IDs;
                var displayValues;

                for(key in selectedIDs) {
                    if(IDs == null)
                        IDs = key;
                    else
                        IDs = IDs + \',\' + key;

                    if(displayValues == null)
                        displayValues = selectedIDs[key];
                    else
                        displayValues = displayValues + \'\\n\' + selectedIDs[key];
                }

                if(IDs == null) {
                    document.getElementById(\''.$fieldname.'\').value = "00000000000";
                    document.getElementById(\''.$fieldname.'_display\').value = "";
                }else{
                    document.getElementById(\''.$fieldname.'\').value = IDs;
                    document.getElementById(\''.$fieldname.'_display\').value = displayValues;
                }
            }

            </script>';

        if ($this->relationObject->getRelationType() == 'MANY-TO-MANY') {
            $classNameLeft = $this->relationObject->getRelatedClass('left');
            $classNameRight = $this->relationObject->getRelatedClass('right');

            if ($this->accessingClassName == $classNameLeft) {
                $tmpObject = new $classNameRight();
                $fieldName = $this->relationObject->getRelatedClassDisplayField('right');
                $fieldLabel = $tmpObject->getDataLabel($fieldName);
                $oidLabel = $tmpObject->getDataLabel('ID');

                $objects = $tmpObject->loadAll(0, 0, 'ID', 'ASC', true);

                self::$logger->debug('['.count($objects).'] related ['.$classNameLeft.'] objects loaded');
            } else {
                $tmpObject = new $classNameLeft();
                $fieldName = $this->relationObject->getRelatedClassDisplayField('left');
                $fieldLabel = $tmpObject->getDataLabel($fieldName);
                $oidLabel = $tmpObject->getDataLabel('ID');

                $objects = $tmpObject->loadAll(0, 0, 'ID', 'ASC', true);

                self::$logger->debug('['.count($objects).'] related ['.$classNameLeft.'] objects loaded');
            }

            $html .= '<table cols="3" class="table table-bordered">';
            $html .= '<tr>';
            $html .= '<th>'.$oidLabel.'</th>';
            $html .= '<th>'.$fieldLabel.'</th>';
            $html .= '<th>Connect?</th>';
            $html .= '</tr>';

            foreach ($objects as $obj) {
                $html .= '<tr>';
                $html .= '<td width="20%">';
                $html .= $obj->getID();
                $html .= '</td>';
                $html .= '<td width="60%">';
                $html .= $obj->get($fieldName);
                $html .= '</td>';
                $html .= '<td width="20%">';

                if (in_array($obj->getID(), $lookupIDs, true)) {
                    $this->onloadJS .= 'toggelOID(\''.$obj->getID().'\',\''.$obj->get($fieldName).'\',true);';
                    $html .= '<input name = "'.$obj->getID().'" type="checkbox" checked onclick="toggelOID(\''.$obj->getID().'\',\''.$obj->get($fieldName).'\',this.checked);"/>';
                } else {
                    $html .= '<input name = "'.$obj->getID().'" type="checkbox" onclick="toggelOID(\''.$obj->getID().'\',\''.$obj->get($fieldName).'\',this.checked);"/>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $className = $this->relationObject->getRelatedClass();

            $tmpObject = new $className();
            $label = $tmpObject->getDataLabel($this->relationObject->getRelatedClassDisplayField());
            $oidLabel = $tmpObject->getDataLabel('ID');

            $objects = $tmpObject->loadAll(0, 0, 'ID', 'DESC');

            $html = '<table cols="3" width="100%" class="bordered">';
            $html .= '<tr>';
            $html .= '<th>'.$oidLabel.'</th>';
            $html .= '<th>'.$label.'</th>';
            $html .= '<th>Connect?</th>';
            $html .= '</tr>';

            foreach ($objects as $obj) {
                $html .= '<tr>';
                $html .= '<td width="20%">';
                $html .= $obj->getID();
                $html .= '</td>';
                $html .= '<td width="60%">';
                $html .= $obj->get($this->relationObject->getRelatedClassDisplayField());
                $html .= '</td>';
                $html .= '<td width="20%">';
                if ($obj->getID() == $this->relationObject->getValue()) {
                    $html .= '<img src="'.$config->get('app.url').'/images/icons/accept_ghost.png"/>';
                } else {
                    $tmp = new Button("document.getElementById('".$fieldname."').value = '".$obj->getID()."'; document.getElementById('".$fieldname."_display').value = '".$obj->get($this->relationObject->getRelatedClassDisplayField())."'; $('[Id=".$fieldname."_display]').blur(); window.jQuery.dialog.close();", '', 'selBut', $config->get('app.url').'/images/icons/accept.png');
                    $html .= $tmp->render();
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '<script type="text/javascript">'.
                '$(document).ready(function() {';

        $html .= $this->onloadJS;

        $html .= '});</script>';

        self::$logger->debug('<<renderSelector[html]');

        return $html;
    }
}
