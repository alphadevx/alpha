<?php

namespace Alpha\View\Renderer\Html;

use Alpha\View\Renderer\RendererProviderInterface;
use Alpha\View\Widget\Button;
use Alpha\View\Widget\TextBox;
use Alpha\View\Widget\StringBox;
use Alpha\View\Widget\RecordSelector;
use Alpha\View\View;
use Alpha\View\ViewState;
use Alpha\Controller\Front\FrontController;
use Alpha\Controller\Controller;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\InputFilter;
use Alpha\Model\Type\String;
use Alpha\Model\ActiveRecord;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\AlphaException;
use ReflectionClass;

/**
 * HTML renderer.  Will invoke widgets from the Alpha\View\Widgets package
 * automatically for the correct data type.  Templates from ./templates/html
 * will be loaded by default, but these can be overridden on a per-BO level in
 * the application when required (consider the default ones to be scaffolding).
 *
 * @since 1.2
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
class RendererProviderHTML implements RendererProviderInterface
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger;
     * @since 1.2
     */
    private static $logger = null;

    /**
     * The business object that we are renderering
     *
     * @var Alpha\Model\ActiveRecord
     * @since 1.2
     */
    private $BO;

    /**
     * The constructor
     *
     * @since 1.2
     */
    public function __construct()
    {
        self::$logger = new Logger('RendererProviderHTML');
        self::$logger->debug('>>__construct()');

        self::$logger->debug('<<__construct');
    }

    /**
     * {@inheritDoc}
     */
    public function setBO($BO)
    {
        $this->BO = $BO;
    }

    /**
     * {@inheritDoc}
     */
    public function createView($fields=array())
    {
        self::$logger->debug('>>createView(fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // the form ID
        $fields['formID'] = get_class($this->BO).'_'.$this->BO->getOID();

        // buffer form fields to $formFields
        $fields['formFields'] = $this->renderAllFields('create');

        // buffer HTML output for Create and Cancel buttons
        $button = new Button('submit', 'Create', 'createBut');
        $fields['createButton'] = $button->render();

        $button = new Button("document.location.replace('".FrontController::generateSecureURL('act=Alpha\\Controller\\ListActiveRecordsController')."')", 'Cancel', 'cancelBut');
        $fields['cancelButton'] = $button->render();

        // buffer security fields to $formSecurityFields variable
        $fields['formSecurityFields'] = self::renderSecurityFields();

        self::$logger->debug('<<createView [HTML]');
        return View::loadTemplate($this->BO, 'create', $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function editView($fields=array())
    {
        self::$logger->debug('>>editView(fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // the form ID
        $fields['formID'] = get_class($this->BO).'_'.$this->BO->getOID();

        // buffer form fields to $formFields
        $fields['formFields'] = $this->renderAllFields('edit');

        // buffer HTML output for Create and Cancel buttons
        $button = new Button('submit', 'Save', 'saveBut');
        $fields['saveButton'] = $button->render();

        $js = "if(window.jQuery) {
                    BootstrapDialog.show({
                        title: 'Confirmation',
                        message: 'Are you sure you wish to delete this item?',
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
                                    $('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('deleteOID')) : 'deleteOID')."\"]').attr('value', '".$this->BO->getOID()."');
                                    $('#deleteForm').submit();
                                    dialogItself.close();
                                }
                            }
                        ]
                    });
                }";
        $button = new Button($js, "Delete", "deleteBut");
        $fields['deleteButton'] = $button->render();

        $button = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\ListController&ActiveRecordType='.get_class($this->BO))."'", "Back to List", "cancelBut");
        $fields['cancelButton'] = $button->render();

        // buffer security fields to $formSecurityFields variable
        $fields['formSecurityFields'] = self::renderSecurityFields();

        // OID will need to be posted for optimistic lock checking
        $fields['version_num'] = $this->BO->getVersionNumber();

        self::$logger->debug('<<editView [HTML]');
        return View::loadTemplate($this->BO, 'edit', $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function listView($fields=array())
    {
        self::$logger->debug('>>listView(fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // the form action
        $fields['formAction'] = $_SERVER['REQUEST_URI'];

        // work out how many columns will be in the table
        $reflection = new ReflectionClass(get_class($this->BO));
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

                    if ($propClass == 'Text') {
                        $text = htmlentities($this->BO->get($propName), ENT_COMPAT, 'utf-8');
                        if (mb_strlen($text) > 70)
                            $html .= '  <td>&nbsp;'.mb_substr($text, 0, 70).'...</td>';
                        else
                            $html .= '  <td>&nbsp;'.$text.'</td>';
                    } elseif ($propClass == 'DEnum') {
                        $html .= '  <td>&nbsp;'.$this->BO->getPropObject($propName)->getDisplayValue().'</td>';
                    } else {
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
        if (mb_strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
            $button = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\ViewController&ActiveRecordType='.get_class($this->BO).'&ActiveRecordOID='.$this->BO->getOID())."';", 'View', 'view'.$this->BO->getOID().'But');
            $fields['viewButton'] = $button->render();
        } else {
            if ($this->BO->hasAttribute('URL'))
                $button = new Button("document.location = '".$this->BO->get('URL')."';", 'View', 'view'.$this->BO->getOID().'But');
            else
                $button = new Button("document.location = '".$config->get('app.url')."view/".urlencode(get_class($this->BO))."/".$this->BO->getOID()."';", 'View', 'view'.$this->BO->getOID().'But');

            $fields['viewButton'] = $button->render();
        }

        $html = '';
        // render edit and delete buttons for admins only
        if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
            $html .= '&nbsp;&nbsp;';
            $button = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\EditController&ActiveRecordType='.get_class($this->BO)."&ActiveRecordOID=".$this->BO->getOID())."'", "Edit", "edit".$this->BO->getOID()."But");
            $html .= $button->render();
            $html .= '&nbsp;&nbsp;';

            $js = "if(window.jQuery) {
                    BootstrapDialog.show({
                        title: 'Confirmation',
                        message: 'Are you sure you wish to delete this item?',
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
                                    $('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('deleteOID')) : 'deleteOID')."\"]').attr('value', '".$this->BO->getOID()."');
                                    $('#deleteForm').submit();
                                    dialogItself.close();
                                }
                            }
                        ]
                    });
                }";

            $button = new Button($js, "Delete", "delete".$this->BO->getOID()."But");
            $html .= $button->render();
        }
        $fields['adminButtons'] = $html;

        // buffer security fields to $formSecurityFields variable
        $fields['formSecurityFields'] = self::renderSecurityFields();

        self::$logger->debug('<<listView [HTML]');
        return View::loadTemplate($this->BO, 'list', $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function detailedView($fields=array())
    {
        self::$logger->debug('>>detailedView(fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // we may want to display the OID regardless of class
        $fields['OIDLabel'] = $this->BO->getDataLabel('OID');
        $fields['OID'] = $this->BO->getOID();

        // buffer form fields to $formFields
        $fields['formFields'] = $this->renderAllFields('view');

        // Back button
        $button = new Button('history.back()', 'Back', 'backBut');
        $fields['backButton'] = $button->render();

        $html = '';
        // render edit and delete buttons for admins only
        if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {

            $button = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getOID())."'", "Edit", "editBut");
            $html .= $button->render();

            $js = "if(window.jQuery) {
                    BootstrapDialog.show({
                        title: 'Confirmation',
                        message: 'Are you sure you wish to delete this item?',
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
                                    $('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('deleteOID')) : 'deleteOID')."\"]').attr('value', '".$this->BO->getOID()."');
                                    $('#deleteForm').submit();
                                    dialogItself.close();
                                }
                            }
                        ]
                    });
                }";

            $button = new Button($js, "Delete", "deleteBut");
            $html .= $button->render();
        }
        $fields['adminButtons'] = $html;

        self::$logger->debug('<<detailedView [HTML]');
        return View::loadTemplate($this->BO, 'detail', $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function adminView($fields=array())
    {
        self::$logger->debug('>>adminView(fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // the class name of the BO
        $fields['fullClassName'] = stripslashes(get_class($this->BO));

        // the table name in the DB for the BO
        $fields['tableName'] = $this->BO->getTableName();

        // record count for the BO in the DB
        $fields['count'] = ($this->BO->checkTableExists() ? $this->BO->getCount() : '<span class="warning">unavailable</span>');

        // table exists in the DB?
        $fields['tableExists'] = ($this->BO->checkTableExists() ? '<span class="success">Yes</span>' : '<span class="warning">No</span>');

        if ($this->BO->getMaintainHistory())
            $fields['tableExists'] = ($this->BO->checkTableExists(true) ? '<span class="success">Yes</span>' : '<span class="warning">No history table</span>');

        // table schema needs to be updated in the DB?
        $fields['tableNeedsUpdate'] = ($this->BO->checkTableNeedsUpdate() ? '<span class="warning">Yes</span>' : '<span class="success">No</span>');

        // create button
        if ($this->BO->checkTableExists()) {
            $button = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\\Controller\\CreateController&ActiveRecordType='.get_class($this->BO))."'", "Create New", "create".stripslashes(get_class($this->BO))."But");
            $fields['createButton'] = $button->render();
        } else {
            $fields['createButton'] = '';
        }

        // list all button
        if ($this->BO->checkTableExists()) {
            $button = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\\Controller\\ListController&ActiveRecordType='.get_class($this->BO))."'", "List All", "list".stripslashes(get_class($this->BO))."But");
            $fields['listButton'] = $button->render();
        } else {
            $fields['listButton'] = '';
        }

        // the create table button (if required)
        $html = '';

        if (!$this->BO->checkTableExists()) {
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('createTableBut')) : 'createTableBut');
            $button = new Button('submit', 'Create Table', $fieldname);
            $html .= $button->render();
            // hidden field so that we know which class to create the table for
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('createTableClass')) : 'createTableClass');
            $html .= '<input type="hidden" name="'.$fieldname.'" value="'.get_class($this->BO).'"/>';
        }

        if ($html == '' && $this->BO->getMaintainHistory() && !$this->BO->checkTableExists(true)) {
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('createHistoryTableBut')) : 'createHistoryTableBut');
            $button = new Button('submit', 'Create History Table', $fieldname);
            $html .= $button->render();
            // hidden field so that we know which class to create the table for
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('createTableClass')) : 'createTableClass');
            $html .= '<input type="hidden" name="'.$fieldname.'" value="'.get_class($this->BO).'"/>';
        }
        $fields['createTableButton'] = $html;

        // recreate and update table buttons (if required)
        $html = '';
        if ($this->BO->checkTableNeedsUpdate() && $this->BO->checkTableExists()) {

            $js = "if(window.jQuery) {
                    BootstrapDialog.show({
                        title: 'Confirmation',
                        message: 'Are you sure you wish to recreate this class table (all data will be lost)?',
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
                                    $('[Id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('admin_'.stripslashes(get_class($this->BO)).'_button_pressed')) : 'admin_'.stripslashes(get_class($this->BO)).'_button_pressed')."\"]').attr('value', 'recreateTableBut');
                                    $('#admin_".stripslashes(get_class($this->BO))."').submit();
                                    dialogItself.close();
                                }
                            }
                        ]
                    });
                }";

            $button = new Button($js , "Recreate Table", "recreateTableBut");
            $html .= $button->render();
            // hidden field so that we know which class to recreate the table for
            $html .= '<input type="hidden" name="recreateTableClass" value="'.get_class($this->BO).'"/>';
            $html .= '&nbsp;&nbsp;';

            $js = "if(window.jQuery) {
                    BootstrapDialog.show({
                        title: 'Confirmation',
                        message: 'Are you sure you wish to attempt to modify this class table by adding new attributes?',
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
                                    $('[Id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('admin_'.stripslashes(get_class($this->BO)).'_button_pressed')) : 'admin_'.stripslashes(get_class($this->BO)).'_button_pressed')."\"]').attr('value', 'updateTableBut');
                                    $('#admin_".stripslashes(get_class($this->BO))."').submit();
                                    dialogItself.close();
                                }
                            }
                        ]
                    });
                }";

            $button = new Button($js , "Update Table", "updateTableBut");
            $html .= $button->render();
            // hidden field so that we know which class to update the table for
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('updateTableClass')) : 'updateTableClass');
            $html .= '<input type="hidden" name="'.$fieldname.'" value="'.get_class($this->BO).'"/>';
            // hidden field to tell us which button was pressed
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('admin_'.stripslashes(get_class($this->BO)).'_button_pressed')) : 'admin_'.stripslashes(get_class($this->BO)).'_button_pressed');
            $html .= '<input type="hidden" id="'.$fieldname.'" name="'.$fieldname.'" value=""/>';
        }
        $fields['recreateOrUpdateButtons'] = $html;

        // buffer security fields to $formSecurityFields variable
        $fields['formSecurityFields'] = self::renderSecurityFields();

        self::$logger->debug('<<adminView [HTML]');
        return View::loadTemplate($this->BO, 'admin', $fields);
    }

    /**
     * {@inheritDoc}
     */
    public static function displayPageHead($controller)
    {
        if (self::$logger == null)
            self::$logger = new Logger('RendererProviderHTML');

        self::$logger->debug('>>displayPageHead(controller=['.var_export($controller, true).'])');

        $config = ConfigProvider::getInstance();

        if (!class_exists(get_class($controller)))
            throw new IllegalArguementException('The controller provided ['.get_class($controller).'] is not defined anywhere!');

        $allowCSSOverrides = true;

        if (isset($_SESSION['currentUser']) && ActiveRecord::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && mb_strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
            $allowCSSOverrides = false;
        }

        $html = View::loadTemplateFragment('html', 'head.phtml', array('title' => $controller->getTitle(), 'description' => $controller->getDescription(), 'allowCSSOverrides' => $allowCSSOverrides));

        if (method_exists($controller, 'during_displayPageHead_callback'))
            $html .= $controller->during_displayPageHead_callback();

        $html .= '</head>';

        try {
            if ($controller->getBO() != null)
                $html .= '<body'.($controller->getBO()->get('bodyOnload') != '' ? ' onload="'.$controller->getBO()->get('bodyOnload').'"' : '').'>';
            else
                $html .= '<body>';
        } catch (AlphaException $e) {
            $html .= '<body>';
        }

        $html .= '<div class="container">';

        if (method_exists($controller, 'insert_CMSDisplayStandardHeader_callback'))
            $html.= $controller->insert_CMSDisplayStandardHeader_callback();

        self::$logger->debug('<<displayPageHead [HTML]');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public static function displayPageFoot($controller)
    {
        if (self::$logger == null)
            self::$logger = new Logger('RendererProviderHTML');

        self::$logger->debug('>>displayPageFoot(controller=['.get_class($controller).'])');

        $html = View::loadTemplateFragment('html', 'footer.phtml', array());

        self::$logger->debug('<<displayPageFoot ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public static function displayUpdateMessage($message)
    {
        if (self::$logger == null)
            self::$logger = new Logger('RendererProviderHTML');
        self::$logger->debug('>>displayUpdateMessage(message=['.$message.'])');

        $html = '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'.$message.'</div>';

        self::$logger->debug('<<displayUpdateMessage ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public static function displayErrorMessage($message)
    {
        if (self::$logger == null)
            self::$logger = new Logger('RendererProviderHTML');
        self::$logger->debug('>>displayErrorMessage(message=['.$message.'])');

        $html = '<div class="alert alert-danger">'.$message.'</div>';

        self::$logger->debug('<<displayErrorMessage ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public static function renderErrorPage($code, $message)
    {
        $config = ConfigProvider::getInstance();

        $html = '<html><head>';
        $html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'alpha/lib/jquery/ui/themes/'.$config->get('app.css.theme').'/jquery.ui.all.css">';
        $html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'alpha/css/alpha.css">';
        $html .= '<title>'.$code.' - '.$message.'</title></head>';
        $html .= '<body>';
        $html .= self::displayErrorMessage('<strong>'.$code.':</strong> '.$message);
        $html .= '</body></html>';

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public static function renderDeleteForm($URI)
    {
        if(self::$logger == null)
            self::$logger = new Logger('RendererProviderHTML');
        self::$logger->debug('>>renderDeleteForm()');

        $config = ConfigProvider::getInstance();

        $html = '<form action="'.$URI.'" method="POST" id="deleteForm" accept-charset="UTF-8">';
        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('deleteOID')) : 'deleteOID');
        $html .= '<input type="hidden" name="'.$fieldname.'" id="'.$fieldname.'" value=""/>';
        $html .= self::renderSecurityFields();
        $html .= '</form>';

        self::$logger->debug('<<renderDeleteForm ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public static function renderSecurityFields()
    {
        if (self::$logger == null)
            self::$logger = new Logger('RendererProviderHTML');

        self::$logger->debug('>>renderSecurityFields()');

        $config = ConfigProvider::getInstance();

        $html = '';

        $fields = Controller::generateSecurityFields();

        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt('var1'));
        else
            $fieldname = 'var1';

        $html .= '<input type="hidden" name="'.$fieldname.'" value="'.$fields[0].'"/>';

        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt('var2'));
        else
            $fieldname = 'var2';

        $html .= '<input type="hidden" name="'.$fieldname.'" value="'.$fields[1].'"/>';

        self::$logger->debug('<<renderSecurityFields ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderIntegerField($name, $label, $mode, $value='')
    {
        self::$logger->debug('>>renderIntegerField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();

        if($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt($name));
        else
            $fieldname = $name;

        $html = '<div class="form-group">';
        $html .= '  <label for="'.$fieldname.'">'.$label.'</label>';

        if ($mode == 'create') {
            $html .= '<input type="text" style="width:100%;" name="'.$fieldname.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/>';
        }

        if ($mode == 'edit') {
            $html .= '<input type="text" style="width:100%;" name="'.$fieldname.'" value="'.$value.'"/>';
        }

        $html .= '</div>';

        self::$logger->debug('<<renderIntegerField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderDoubleField($name, $label, $mode, $value='')
    {
        self::$logger->debug('>>renderDoubleField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();

        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt($name));
        else
            $fieldname = $name;

        $html = '<div class="form-group">';
        $html .= '  <label for="'.$fieldname.'">'.$label.'</label>';

        if ($mode == 'create') {
            $html .= '<input type="text" size="13" name="'.$fieldname.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/>';
        }

        if ($mode == 'edit') {
            $html .= '<input type="text" size="13" name="'.$fieldname.'" value="'.$value.'"/>';
        }

        $html .= '</div>';

        self::$logger->debug('<<renderDoubleField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderBooleanField($name, $label, $mode, $value='')
    {
        self::$logger->debug('>>renderBooleanField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();

        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt($name));
        else
            $fieldname = $name;

        $html = '<div class="checkbox">';
        $html .= '  <label>';

        if ($mode == 'create') {
            $html .= '      <input type="hidden" name="'.$fieldname.'" value="0">';
            $html .= '      <input type="checkbox" name="'.$fieldname.'" id="'.$fieldname.'">';
            $html .= '          '.$label;

        }

        if ($mode == 'edit') {
            $html .= '      <input type="hidden" name="'.$fieldname.'" value="0">';
            $html .= '      <input type="checkbox" name="'.$fieldname.'" id="'.$fieldname.'"'.($value == '1'? ' checked':'').' />';
            $html .= '          '.$label;
        }

        $html .= '  </label>';
        $html .= '</div>';

        self::$logger->debug('<<renderBooleanField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderEnumField($name, $label, $mode, $options, $value='')
    {
        self::$logger->debug('>>renderEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();

        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt($name));
        else
            $fieldname = $name;

        $html = '<div class="form-group">';
        $html .= '  <label for="'.$fieldname.'">'.$label.'</label>';

        if ($mode == 'create') {
            $html .= '  <select name="'.$fieldname.'" id="'.$fieldname.'" class="form-control"/>';
            foreach ($options as $val) {
                $html .= '      <option value="'.$val.'">'.$val.'</option>';
            }
            $html .= '  </select>';
        }

        if ($mode == 'edit') {
            $html .= '  <select name="'.$fieldname.'" id="'.$fieldname.'" class="form-control"/>';
            foreach ($options as $val) {
                if ($value == $val)
                    $html .= '      <option value="'.$val.'" selected>'.$val.'</option>';
                else
                    $html .= '      <option value="'.$val.'">'.$val.'</option>';
            }
            $html .= '  </select>';
        }

        $html .= '</div>';

        self::$logger->debug('<<renderEnumField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderDEnumField($name, $label, $mode, $options, $value='')
    {
        self::$logger->debug('>>renderDEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();

        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt($name));
        else
            $fieldname = $name;

        $html = '<div class="form-group">';
        $html .= '  <label for="'.$fieldname.'">'.$label.'</label>';

        if ($mode == 'create') {
            $html .= '  <select name="'.$fieldname.'" id="'.$fieldname.'" class="form-control"/>';
            foreach (array_keys($options) as $index) {
                $html .= '<option value="'.$index.'">'.$options[$index].'</option>';
            }
            $html .= '  </select>';
        }

        if ($mode == 'edit') {
            $html .= '  <select name="'.$fieldname.'" id="'.$fieldname.'" class="form-control"/>';
            foreach (array_keys($options) as $index) {
                if ($value == $index)
                    $html .= '<option value="'.$index.'" selected>'.$options[$index].'</option>';
                else
                    $html .= '<option value="'.$index.'">'.$options[$index].'</option>';
            }
            $html .= '  </select>';
        }

        $html .= '</div>';

        self::$logger->debug('<<renderDEnumField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderDefaultField($name, $label, $mode, $value='')
    {
        self::$logger->debug('>>renderDefaultField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();

        if ($config->get('security.encrypt.http.fieldnames'))
            $fieldname = base64_encode(SecurityUtils::encrypt($name));
        else
            $fieldname = $name;

        $html = '';

        if ($mode == 'create') {
            $html .= '<textarea cols="100" rows="3" name="'.$fieldname.'">'. (isset ($_POST[$name]) ? $_POST[$name] : '').'</textarea>';
        }

        if ($mode == 'edit') {
            $html .= '<textarea cols="100" rows="3" name="'.$fieldname.'">'.$value.'</textarea>';
        }

        if ($mode == 'view') {
            $html .= '<p><strong>'.$label.':</strong> '.$value.'</p>';
        }

        self::$logger->debug('<<renderDefaultField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderTextField($name, $label, $mode, $value='')
    {
        self::$logger->debug('>>renderTextField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();

        $html = '';

        if ($mode == 'create') {
            // give 10 rows for content fields (other 5 by default)
            if ($name == 'content')
                $text = new TextBox($this->BO->getPropObject($name), $label, $name, 10);
            else
                $text = new TextBox($this->BO->getPropObject($name), $label, $name);
            $html .= $text->render();
        }

        if ($mode == 'edit') {
            // give 10 rows for content fields (other 5 by default)
            if ($name == 'content') {
                $viewState = ViewState::getInstance();

                if ($viewState->get('markdownTextBoxRows') == '')
                    $text = new TextBox($this->BO->getPropObject($name), $label, $name, 10);
                else
                    $text = new TextBox($this->BO->getPropObject($name), $label, $name, (integer)$viewState->get('markdownTextBoxRows'));

                $html .= $text->render();
            } else {
                $text = new TextBox($this->BO->getPropObject($name), $label, $name);
                $html .= $text->render();
            }
        }

        if ($mode == 'view') {
            $html .= '<p><strong>';

            $html .= $label;

            $html .= ':</strong>';

            // filter ouput to prevent malicious injection
            $value = InputFilter::encode($value);

            // ensures that line returns are rendered
            $value = str_replace("\n", '<br>', $value);

            $html .= '&nbsp;';

            $html .= $value;

            $html .= '</p>';
        }

        self::$logger->debug('<<renderTextField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderStringField($name, $label, $mode, $value='')
    {
        self::$logger->debug('>>renderStringField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();

        $html = '';

        if ($mode == 'create' || $mode == 'edit') {
            $string = new StringBox($this->BO->getPropObject($name), $label, $name);
            $html .= $string->render();
        }

        if ($mode == 'view') {
            $html .= '<p><strong>'.$label.':</strong> '.$value.'</p>';
        }

        self::$logger->debug('<<renderStringField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderRelationField($name, $label, $mode, $value = '', $expanded = false, $buttons = true)
    {
        self::$logger->debug('>>renderRelationField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], expanded=['.$expanded.'], buttons=['.$buttons.'])');

        $config = ConfigProvider::getInstance();

        $html = '';

        $rel = $this->BO->getPropObject($name);

        if ($mode == 'create' || $mode == 'edit') {
            if ($rel->getRelationType() == 'MANY-TO-MANY') {
                try {
                    // check to see if the rel is on this class
                    $rel->getSide(get_class($this->BO));
                    $widget = new RecordSelector($rel, $label, $name, get_class($this->BO));
                    $html .= $widget->render($expanded, $buttons);
                } catch (IllegalArguementException $iae) {
                    // the rel may be on a parent class
                    $parentClassName = ucfirst($this->BO->getTableName()).'Object';
                    $widget = new RecordSelector($rel, $label, $name, $parentClassName);
                    $html .= $widget->render($expanded, $buttons);
                }
            } else {
                $rel = new RecordSelector($rel, $label, $name);
                $html .= $rel->render($expanded, $buttons);
            }
        }

        if ($mode == 'view') {
            if ($rel->getRelationType() == 'MANY-TO-ONE') {
                $html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue());
            } elseif ($rel->getRelationType() == 'MANY-TO-MANY') {
                try {
                    // check to see if the rel is on this class
                    $rel->getSide(get_class($this->BO));
                    $html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue(get_class($this->BO)));
                } catch (IllegalArguementException $iae) {
                    // the rel may be on a parent class
                    $parentClassName = ucfirst($this->BO->getTableName()).'Object';
                    $html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue($parentClassName));
                }
            } else {
                $rel = new RecordSelector($rel, $label, $name);
                $html .= $rel->render($expanded, $buttons);
            }
        }

        self::$logger->debug('<<renderRelationField ['.$html.']');
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderAllFields($mode, $filterFields=array(), $readOnlyFields=array())
    {
        self::$logger->debug('>>renderAllFields(mode=['.$mode.'], filterFields=['.var_export($filterFields, true).'], readOnlyFields=['.var_export($readOnlyFields, true).'])');

        $html = '';

        // get the class attributes
        $properties = array_keys($this->BO->getDataLabels());

        $orignalMode = $mode;

        foreach ($properties as $propName) {
            if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $filterFields)) {
                // render readonly fields in the supplied array
                if (in_array($propName, $readOnlyFields))
                    $mode = 'view';
                else
                    $mode = $orignalMode;

                if (!is_object($this->BO->getPropObject($propName)))
                    continue;

                $reflection = new ReflectionClass($this->BO->getPropObject($propName));
                $propClass = $reflection->getShortName();

                // exclude non-Relation transient attributes from create and edit screens
                if ($propClass != 'Relation' && ($mode == 'edit' || $mode == 'create') && in_array($propName, $this->BO->getTransientAttributes()))
                    continue;

                switch (mb_strtoupper($propClass)) {
                    case 'INTEGER' :
                        if ($mode == 'view') {
                            $html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
                        } else {
                            $html .= $this->renderIntegerField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
                        }
                    break;
                    case 'DOUBLE' :
                        if ($mode == 'view') {
                            $html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
                        } else {
                            $html .= $this->renderDoubleField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
                        }
                    break;
                    case 'DATE' :
                        if ($mode == 'view') {
                            $value = $this->BO->get($propName);
                            if ($value == '0000-00-00')
                                $value = '';
                            $html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $value);
                        } else {
                            $date = new DateBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
                            $html .= $date->render();
                        }
                    break;
                    case 'TIMESTAMP' :
                        if ($mode == 'view') {
                            $value = $this->BO->get($propName);
                            if ($value == '0000-00-00 00:00:00')
                                $value = '';
                            $html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $value);
                        } else {
                            $timestamp = new DateBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
                            $html .= $timestamp->render();
                        }
                    break;
                    case 'STRING' :
                        $html .= $this->renderStringField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
                    break;
                    case 'TEXT' :
                        $html .= $this->renderTextField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
                    break;
                    case 'BOOLEAN' :
                        if ($mode == 'view') {
                            $html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
                        } else {
                            $html .= $this->renderBooleanField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
                        }
                    break;
                    case 'ENUM' :
                        if ($mode == 'view') {
                            $html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
                        } else {
                            $enum = $this->BO->getPropObject($propName);
                            $html .= $this->renderEnumField($propName, $this->BO->getDataLabel($propName), $mode, $enum->getOptions(), $this->BO->get($propName));
                        }
                    break;
                    case 'DENUM' :
                        if ($mode == 'view') {
                            $html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->getPropObject($propName)->getDisplayValue());
                        } else {
                            $denum = $this->BO->getPropObject($propName);
                            $html .= $this->renderDEnumField($propName, $this->BO->getDataLabel($propName), $mode, $denum->getOptions(), $this->BO->get($propName));
                        }
                    break;
                    case 'RELATION' :
                        $html .= $this->renderRelationField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
                    break;
                    default :
                        $html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
                    break;
                }
            }
        }

        self::$logger->debug('<<renderAllFields ['.$html.']');
        return $html;
    }
}

?>