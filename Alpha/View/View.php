<?php

namespace Alpha\View;

use Alpha\Controller\Front\FrontController;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Type\DEnum;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Service\ServiceFactory;
use Alpha\View\Renderer\RendererProviderInterface;
use ReflectionClass;

/**
 * The master rendering view class for the Alpha Framework.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2022, John Collins (founder of Alpha Framework).
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
class View
{
    /**
     * The business object that will be rendered.
     *
     * @var \Alpha\Model\ActiveRecord
     *
     * @since 1.0
     */
    protected $record;

    /**
     * The rendering provider that will be used to render the active record.
     *
     * @var \Alpha\View\Renderer\RendererProviderInterface
     *
     * @since 1.2
     */
    private static $provider;

    /**
     * Trace logger.
     *
     * @var Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor for the View.  As this is protected, use the View::getInstance method from a public scope.
     *
     * @param ActiveRecord $record           The main business object that this view is going to render
     * @param string       $acceptHeader Optionally pass the HTTP Accept header to select the correct renderer provider.
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    protected function __construct(ActiveRecord $record, string $acceptHeader = null)
    {
        self::$logger = new Logger('View');
        self::$logger->debug('>>__construct(Record=['.var_export($record, true).'], acceptHeader=['.$acceptHeader.'])');

        $config = ConfigProvider::getInstance();

        if ($record instanceof ActiveRecord) {
            $this->record = $record;
        } else {
            throw new IllegalArguementException('The record type provided ['.get_class($record).'] is not defined anywhere!');
        }

        self::setProvider($config->get('app.renderer.provider.name'), $acceptHeader);
        self::$provider->setRecord($this->record);

        self::$logger->debug('<<__construct');
    }

    /**
     * Static method which returns a View object or a custom child view for the Record specified
     * if one exists.
     *
     * @param ActiveRecord $record           The main business object that this view is going to render
     * @param bool         $returnParent Flag to enforce the return of this object instead of a child (defaults to false)
     * @param string       $acceptHeader Optionally pass the HTTP Accept header to select the correct renderer provider.
     *
     * @since 1.0
     */
    public static function getInstance(ActiveRecord $record, bool $returnParent = false, string $acceptHeader = null): mixed
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }
        self::$logger->debug('>>getInstance(Record=['.var_export($record, true).'], returnParent=['.$returnParent.'], acceptHeader=['.$acceptHeader.'])');

        $class = new ReflectionClass($record);
        $childView = $class->getShortname();
        $childView = $childView.'View';

        // Check to see if a custom view exists for this record, and if it does return that view instead
        if (!$returnParent) {
            $className = '\Alpha\View\\'.$childView;

            if (class_exists($className)) {
                self::$logger->debug('<<getInstance [new '.$className.'('.get_class($record).')]');

                $instance = new $className($record, $acceptHeader);

                return $instance;
            }

            $className = '\View\\'.$childView;

            if (class_exists('\View\\'.$childView)) {
                self::$logger->debug('<<getInstance [new '.$className.'('.get_class($record).')]');

                $instance = new $className($record, $acceptHeader);

                return $instance;
            }

            self::$logger->debug('<<getInstance [new View('.get_class($record).', '.$acceptHeader.')]');

            return new self($record, $acceptHeader);
        } else {
            self::$logger->debug('<<getInstance [new View('.get_class($record).', '.$acceptHeader.')]');

            return new self($record, $acceptHeader);
        }
    }

    /**
     * Simple setter for the view business object.
     *
     * @param \Alpha\Model\ActiveRecord $record
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function setRecord(\Alpha\Model\ActiveRecord $record): void
    {
        self::$logger->debug('>>setRecord(Record=['.var_export($record, true).'])');

        if ($record instanceof \Alpha\Model\ActiveRecord) {
            $this->record = $record;
            self::$provider->setRecord($this->record);
        } else {
            throw new IllegalArguementException('The Record provided ['.get_class($record).'] is not defined anywhere!');
        }

        self::$logger->debug('<<setRecord');
    }

    /**
     * Gets the Record attached to this view (if any).
     *
     * @since 1.0
     */
    public function getRecord(): \Alpha\Model\ActiveRecord
    {
        return $this->record;
    }

    /**
     * Renders the default create view.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @since 1.0
     */
    public function createView(array $fields = array()): string
    {
        self::$logger->debug('>>createView(fields=['.var_export($fields, true).'])');

        if (method_exists($this, 'beforeCreateView')) {
            $this->{'beforeCreateView'}();
        }

        $body = self::$provider->createView($fields);

        if (method_exists($this, 'afterCreateView')) {
            $this->{'afterCreateView'}();
        }

        self::$logger->debug('<<createView');

        return $body;
    }

    /**
     * Renders a form to enable object editing.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @since 1.0
     */
    public function editView(array $fields = array()): string
    {
        self::$logger->debug('>>editView(fields=['.var_export($fields, true).'])');

        if (method_exists($this, 'beforeEditView')) {
            $this->{'beforeEditView'}();
        }

        $body = self::$provider->editView($fields);

        if (method_exists($this, 'afterEditView')) {
            $this->{'afterEditView'}();
        }

        self::$logger->debug('<<editView');

        return $body;
    }

    /**
     * Renders the list view.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @since 1.0
     */
    public function listView(array $fields = array()): string
    {
        self::$logger->debug('>>listView(fields=['.var_export($fields, true).'])');

        if (method_exists($this, 'beforeListView')) {
            $this->{'beforeListView'}();
        }

        $body = self::$provider->listView($fields);

        if (method_exists($this, 'afterListView')) {
            $this->{'afterListView'}();
        }

        self::$logger->debug('<<listView');

        return $body;
    }

    /**
     * Renders a detailed view of the object (read-only).
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @since 1.0
     */
    public function detailedView(array $fields = array()): string
    {
        self::$logger->debug('>>detailedView(fields=['.var_export($fields, true).'])');

        if (method_exists($this, 'beforeDetailedView')) {
            $this->{'beforeDetailedView'}();
        }

        $body = self::$provider->detailedView($fields);

        if (method_exists($this, 'afterDetailedView')) {
            $this->{'afterDetailedView'}();
        }

        self::$logger->debug('<<detailedView');

        return $body;
    }

    /**
     * Renders the admin view for the business object screen.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @since 1.0
     */
    public function adminView(array $fields = array()): string
    {
        self::$logger->debug('>>adminView(fields=['.var_export($fields, true).'])');

        if (method_exists($this, 'beforeAdminView')) {
            $this->{'beforeAdminView'}();
        }

        $body = self::$provider->adminView($fields);

        if (method_exists($this, 'afterAdminView')) {
            $this->{'afterAdminView'}();
        }

        self::$logger->debug('<<adminView');

        return $body;
    }

    /**
     * Method to render the page header content.
     *
     * @param \Alpha\Controller\Controller $controller
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public static function displayPageHead(\Alpha\Controller\Controller $controller): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }
        self::$logger->debug('>>displayPageHead(controller=['.var_export($controller, true).'])');

        if (method_exists($controller, 'beforeDisplayPageHead')) {
            $controller->{'beforeDisplayPageHead'}();
        }

        $config = ConfigProvider::getInstance();

        if (!self::$provider instanceof RendererProviderInterface) {
            self::setProvider($config->get('app.renderer.provider.name'));
        }

        $provider = self::$provider;
        $header = $provider::displayPageHead($controller);

        if (method_exists($controller, 'afterDisplayPageHead')) {
            $header .= $controller->{'afterDisplayPageHead'}();
        }

        self::$logger->debug('<<displayPageHead ['.$header.']');

        return $header;
    }

    /**
     * Method to render the page footer content.
     *
     * @param \Alpha\Controller\Controller $controller
     *
     * @since 1.0
     */
    public static function displayPageFoot(\Alpha\Controller\Controller $controller): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }

        self::$logger->debug('>>displayPageFoot(controller=['.get_class($controller).'])');

        $config = ConfigProvider::getInstance();

        $footer = '';

        if (method_exists($controller, 'beforeDisplayPageFoot')) {
            $footer .= $controller->beforeDisplayPageFoot();
        }

        if (!self::$provider instanceof RendererProviderInterface) {
            self::setProvider($config->get('app.renderer.provider.name'));
        }

        $provider = self::$provider;
        $footer .= $provider::displayPageFoot($controller);

        if (method_exists($controller, 'afterDisplayPageFoot')) {
            $footer .= $controller->{'afterDisplayPageFoot'}();
        }

        self::$logger->debug('<<displayPageFoot ['.$footer.']');

        return $footer;
    }

    /**
     * Method for rendering the pagination links.
     *
     * @param \Alpha\Controller\Controller $controller
     *
     * @since 3.0
     */
    public static function displayPageLinks(\Alpha\Controller\Controller $controller): string
    {
        $config = ConfigProvider::getInstance();

        $html = '<nav>';
        $recordCount = $controller->getRecordCount();
        $start = $controller->getStart();
        $limit = $controller->getLimit();

        // the index of the last record displayed on this page
        $last = $start+$config->get('app.list.page.amount');

        // ensure that the last index never overruns the total record count
        if ($last > $recordCount) {
            $last = $recordCount ;
        }

        // render a message for an empty list
        if ($recordCount  > 0) {
            $html .= '<ul class="pagination">';
        } else {
            $html .= '<p align="center">The list is empty.&nbsp;&nbsp;</p>';

            return $html;
        }

        // render "Previous" link
        if ($start  > 0) {
            // handle secure URLs
            if ($controller->getRequest()->getParam('token', null) != null) {
                $url = FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.$controller->getRequest()->getParam('ActiveRecordType').'&start='.($start-$controller->getLimit()).'&limit='.$limit);
            } else {
                $url = '/records/'.urlencode($controller->getRequest()->getParam('ActiveRecordType')).'/'.($start-$limit).'/'.$limit;
            }
            $html .= '<li class="page-item"><a class="page-link" href="'.$url.'">&lt;&lt;-Previous</a></li>';
        } elseif ($recordCount  > $limit) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">&lt;&lt;-Previous</a></li>';
        }

        // render the page index links
        if ($recordCount  > $limit) {
            $page = 1;

            for ($i = 0; $i < $recordCount ; $i += $limit) {
                if ($i != $start) {
                    // handle secure URLs
                    if ($controller->getRequest()->getParam('token', null) != null) {
                        $url = FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.$controller->getRequest()->getParam('ActiveRecordType').'&start='.$i.'&limit='.$limit);
                    } else {
                        $url = '/records/'.urlencode($controller->getRequest()->getParam('ActiveRecordType')).'/'.$i.'/'.$limit;
                    }
                    $html .= '<li class="page-item"><a class="page-link" href="'.$url.'">'.$page.'</a></li>';
                } elseif ($recordCount  > $limit) { // render an anchor for the current page
                    $html .= '<li class="page-item active"><a class="page-link" href="#">'.$page.'</a></li>';
                }

                ++$page;
            }
        }

        // render "Next" link
        if ($recordCount  > $last) {
            // handle secure URLs
            if ($controller->getRequest()->getParam('token', null) != null) {
                $url = FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.$controller->getRequest()->getParam('ActiveRecordType').'&start='.($start+$limit).'&limit='.$limit);
            } else {
                $url = '/records/'.urlencode($controller->getRequest()->getParam('ActiveRecordType')).'/'.($start+$limit.'/'.$limit);
            }
            $html .= '<li class="page-item"><a class="page-link" href="'.$url.'">Next-&gt;&gt;</a></li>';
        } elseif ($recordCount  > $limit) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">Next-&gt;&gt;</a></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Renders the content for an update (e.g. successful save) message.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public static function displayUpdateMessage(string $message): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }
        self::$logger->debug('>>displayUpdateMessage(message=['.$message.'])');

        $config = ConfigProvider::getInstance();

        if (!self::$provider instanceof RendererProviderInterface) {
            self::setProvider($config->get('app.renderer.provider.name'));
        }

        $provider = self::$provider;
        $message = $provider::displayUpdateMessage($message);

        self::$logger->debug('<<displayUpdateMessage ['.$message.']');

        return $message;
    }

    /**
     * Renders the content for an error (e.g. save failed) message.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public static function displayErrorMessage(string $message): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }
        self::$logger->debug('>>displayErrorMessage(message=['.$message.'])');

        $config = ConfigProvider::getInstance();

        if (!self::$provider instanceof RendererProviderInterface) {
            self::setProvider($config->get('app.renderer.provider.name'));
        }

        $provider = self::$provider;
        $message = $provider::displayErrorMessage($message);

        self::$logger->debug('<<displayErrorMessage ['.$message.']');

        return $message;
    }

    /**
     * Renders an error page with the supplied error code (typlically a HTTP code) and a message.
     *
     * @param string $code
     * @param string $message
     *
     * @since 1.0
     */
    public static function renderErrorPage(string $code, string $message): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }
        self::$logger->debug('>>renderErrorPage(code=['.$code.'],message=['.$message.'])');

        $config = ConfigProvider::getInstance();

        if (!self::$provider instanceof RendererProviderInterface) {
            self::setProvider($config->get('app.renderer.provider.name'));
        }

        $provider = self::$provider;
        $message = $provider::renderErrorPage($code, $message);

        self::$logger->debug('<<renderErrorPage ['.$message.']');

        return $message;
    }

    /**
     * Method to render a hidden HTML form for posting the ID of an object to be deleted.
     *
     * @param string $URI The URI that the form will point to
     *
     * @since 1.0
     */
    public static function renderDeleteForm(string $URI): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }
        self::$logger->debug('>>renderDeleteForm()');

        $config = ConfigProvider::getInstance();

        if (!self::$provider instanceof RendererProviderInterface) {
            self::setProvider($config->get('app.renderer.provider.name'));
        }

        $provider = self::$provider;
        $html = $provider::renderDeleteForm($URI);

        self::$logger->debug('<<renderDeleteForm ['.$html.']');

        return $html;
    }

    /**
     * Method to render a HTML form with two hidden, hashed (MD5) form fields to be used as
     * a check to ensure that a post to the controller is being sent from the same server
     * as hosting it.
     *
     * @since 1.0
     */
    public static function renderSecurityFields(): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }
        self::$logger->debug('>>renderSecurityFields()');

        $config = ConfigProvider::getInstance();

        if (!self::$provider instanceof RendererProviderInterface) {
            self::setProvider($config->get('app.renderer.provider.name'));
        }

        $provider = self::$provider;
        $html = $provider::renderSecurityFields();

        self::$logger->debug('<<renderSecurityFields ['.$html.']');

        return $html;
    }

    /**
     * Method to render the default Integer HTML.
     *
     * @param string $name      The field name
     * @param string $label     The label to apply to the field
     * @param string $mode      The field mode (create/edit/view)
     * @param string $value     The field value (optional)
     *
     * @since 1.0
     */
    public function renderIntegerField(string $name, string $label, string $mode, string $value = ''): string
    {
        self::$logger->debug('>>renderIntegerField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.']');

        $html = self::$provider->renderIntegerField($name, $label, $mode, $value);

        self::$logger->debug('<<renderIntegerField ['.$html.']');

        return $html;
    }

    /**
     * Method to render the default Double HTML.
     *
     * @param string $name      The field name
     * @param string $label     The label to apply to the field
     * @param string $mode      The field mode (create/edit/view)
     * @param string $value     The field value (optional)
     *
     * @since 1.0
     */
    public function renderDoubleField(string $name, string $label, string $mode, string $value = ''): string
    {
        self::$logger->debug('>>renderDoubleField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $html = self::$provider->renderDoubleField($name, $label, $mode, $value);

        self::$logger->debug('<<renderDoubleField ['.$html.']');

        return $html;
    }

    /**
     * Method to render the default Boolean HTML.
     *
     * @param string $name      The field name
     * @param string $label     The label to apply to the field
     * @param string $mode      The field mode (create/edit/view)
     * @param string $value     The field value (optional)
     *
     * @since 1.0
     */
    public function renderBooleanField(string $name, string $label, string $mode, string $value = ''): string
    {
        self::$logger->debug('>>renderBooleanField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $html = self::$provider->renderBooleanField($name, $label, $mode, $value);

        self::$logger->debug('<<renderBooleanField ['.$html.']');

        return $html;
    }

    /**
     * Method to render the default Enum HTML.
     *
     * @param string $name      The field name
     * @param string $label     The label to apply to the field
     * @param string $mode      The field mode (create/edit/view)
     * @param array  $options   The Enum options
     * @param string $value     The field value (optional)
     *
     * @since 1.0
     */
    public function renderEnumField(string $name, string $label, string $mode, array $options, string $value = ''): string
    {
        self::$logger->debug('>>renderEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $html = self::$provider->renderEnumField($name, $label, $mode, $options, $value);

        self::$logger->debug('<<renderEnumField ['.$html.']');

        return $html;
    }

    /**
     * Method to render the default DEnum HTML.
     *
     * @param string $name      The field name
     * @param string $label     The label to apply to the field
     * @param string $mode      The field mode (create/edit/view)
     * @param array  $options   The DEnum options
     * @param string $value     The field value (optional)
     *
     * @since 1.0
     */
    public function renderDEnumField(string $name, string $label, string $mode, array $options, string $value = ''): string
    {
        self::$logger->debug('>>renderDEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $html = self::$provider->renderDEnumField($name, $label, $mode, $options, $value);

        self::$logger->debug('<<renderDEnumField ['.$html.']');

        return $html;
    }

    /**
     * Method to render the default field HTML when type is not known.
     *
     * @param string $name      The field name
     * @param string $label     The label to apply to the field
     * @param string $mode      The field mode (create/edit/view)
     * @param string $value     The field value (optional)
     *
     * @since 1.0
     */
    public function renderDefaultField(string $name, string $label, string $mode, string $value = ''): string
    {
        self::$logger->debug('>>renderDefaultField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $html = self::$provider->renderDefaultField($name, $label, $mode, $value);

        self::$logger->debug('<<renderDefaultField ['.$html.']');

        return $html;
    }

    /**
     * render the default Text HTML.
     *
     * @param string $name      The field name
     * @param string $label     The label to apply to the field
     * @param string $mode      The field mode (create/edit/view)
     * @param string $value     The field value (optional)
     *
     * @since 1.0
     */
    public function renderTextField(string $name, string $label, string $mode, string $value = ''): string
    {
        self::$logger->debug('>>renderTextField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

        $html = self::$provider->renderTextField($name, $label, $mode, $value);

        self::$logger->debug('<<renderTextField ['.$html.']');

        return $html;
    }

    /**
     * render the default Relation HTML.
     *
     * @param string $name      The field name
     * @param string $label     The label to apply to the field
     * @param string $mode      The field mode (create/edit/view)
     * @param string $value     The field value (optional)
     * @param bool   $expanded  Render the related fields in expanded format or not (optional)
     * @param bool   $buttons   Render buttons for expanding/contacting the related fields (optional)
     *
     * @since 1.0
     */
    public function renderRelationField(string $name, string $label, string $mode, string $value = '', bool $expanded = false, bool $buttons = true): string
    {
        self::$logger->debug('>>renderRelationField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], expanded=['.$expanded.'], buttons=['.$buttons.'])');

        $html = self::$provider->renderRelationField($name, $label, $mode, $value, $expanded, $buttons);

        self::$logger->debug('<<renderRelationField ['.$html.']');

        return $html;
    }

    /**
     * Renders all fields for the current Record in edit/create/view mode.
     *
     * @param string $mode           (view|edit|create)
     * @param array  $filterFields   Optional list of field names to exclude from rendering
     * @param array  $readOnlyFields Optional list of fields to render in a readonly fashion when rendering in create or edit mode
     *
     * @since 1.0
     */
    public function renderAllFields(string $mode, array $filterFields = array(), array $readOnlyFields = array()): string
    {
        self::$logger->debug('>>renderAllFields(mode=['.$mode.'], filterFields=['.var_export($filterFields, true).'], readOnlyFields=['.var_export($readOnlyFields, true).'])');

        $html = self::$provider->renderAllFields($mode, $filterFields, $readOnlyFields);

        self::$logger->debug('<<renderAllFields ['.$html.']');

        return $html;
    }

    /**
     * Loads a template for the Record specified if one exists.  Lower level custom templates
     * take precedence.
     *
     * @param \Alpha\Model\ActiveRecord $record
     * @param string                   $mode
     * @param array                    $fields
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public static function loadTemplate(\Alpha\Model\ActiveRecord $record, string $mode, array $fields = array()): string
    {
        self::$logger->debug('>>loadTemplate(Record=['.var_export($record, true).'], mode=['.$mode.'], fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // for each Record property, create a local variable holding its value
        $reflection = new ReflectionClass(get_class($record));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if ($propName != 'logger' && !$propObj->isPrivate()) {
                $prop = $record->getPropObject($propName);
                if ($prop instanceof DEnum) {
                    ${$propName} = $prop->getDisplayValue();
                } else {
                    ${$propName} = $record->get($propName);
                }
            }
        }

        // loop over the $fields array and create a local variable for each key value
        foreach (array_keys($fields) as $fieldName) {
            ${$fieldName} = $fields[$fieldName];
        }

        $filename = $mode.'.phtml';
        $class = new ReflectionClass($record);
        $className = $class->getShortname();

        $customPath = $config->get('app.root').'src/View/Html/Templates/'.$className.'/'.$filename;
        $defaultPath1 = $config->get('app.root').'vendor/alphadevx/alpha/Alpha/View/Renderer/Html/Templates/'.$className.'/'.$filename;
        $defaultPath2 = $config->get('app.root').'vendor/alphadevx/alpha/Alpha/View/Renderer/Html/Templates/'.$filename;
        $defaultPath3 = $config->get('app.root').'Alpha/View/Renderer/Html/Templates/'.$className.'/'.$filename;
        $defaultPath4 = $config->get('app.root').'Alpha/View/Renderer/Html/Templates/'.$filename;

        // Check to see if a custom template exists for this record, and if it does load that
        if (file_exists($customPath)) {
            self::$logger->debug('Loading template ['.$customPath.']');
            ob_start();
            require $customPath;
            $html = ob_get_clean();

            self::$logger->debug('<<loadTemplate');
            return $html;
        } elseif (file_exists($defaultPath1)) {
            self::$logger->debug('Loading template ['.$defaultPath1.']');
            ob_start();
            require $defaultPath1;
            $html = ob_get_clean();

            self::$logger->debug('<<loadTemplate');
            return $html;
        } elseif (file_exists($defaultPath2)) {
            self::$logger->debug('Loading template ['.$defaultPath2.']');
            ob_start();
            require $defaultPath2;
            $html = ob_get_clean();

            self::$logger->debug('<<loadTemplate');
            return $html;
        } elseif (file_exists($defaultPath3)) {
            self::$logger->debug('Loading template ['.$defaultPath3.']');
            ob_start();
            require $defaultPath3;
            $html = ob_get_clean();

            self::$logger->debug('<<loadTemplate');
            return $html;
        } elseif (file_exists($defaultPath4)) {
            self::$logger->debug('Loading template ['.$defaultPath4.']');
            ob_start();
            require $defaultPath4;
            $html = ob_get_clean();

            self::$logger->debug('<<loadTemplate');
            return $html;
        } else {
            self::$logger->debug('<<loadTemplate');
            throw new IllegalArguementException('No ['.$mode.'] HTML template found for class ['.$className.']');
        }
    }

    /**
     * Loads a template fragment from the Renderer/[type]/Fragments/[filename.ext] location.
     *
     * @param string $type     Currently only html supported, later json and xml.
     * @param string $fileName The name of the fragment file
     * @param array  $fields   A hash array of field values to pass to the template fragment.
     *
     * @since 1.2
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public static function loadTemplateFragment(string $type, string $fileName, array $fields = array()): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('View');
        }
        self::$logger->debug('>>loadTemplateFragment(type=['.$type.'], fileName=['.$fileName.'], fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // loop over the $fields array and create a local variable for each key value
        foreach (array_keys($fields) as $fieldName) {
            ${$fieldName} = $fields[$fieldName];
        }

        $customPath = $config->get('app.root').'src/View/'.ucfirst($type).'/Fragments/'.$fileName;
        $defaultPath1 = $config->get('app.root').'vendor/alphadevx/alpha/Alpha/View/Renderer/'.ucfirst($type).'/Fragments/'.$fileName;
        $defaultPath2 = $config->get('app.root').'Alpha/View/Renderer/'.ucfirst($type).'/Fragments/'.$fileName;

        // Check to see if a custom template exists for this record, and if it does load that
        if (file_exists($customPath)) {
            self::$logger->debug('Loading template ['.$customPath.']');
            ob_start();
            require $customPath;
            $html = ob_get_clean();

            self::$logger->debug('<<loadTemplateFragment');
            return $html;
        } elseif (file_exists($defaultPath1)) {
            self::$logger->debug('Loading template ['.$defaultPath1.']');
            ob_start();
            require $defaultPath1;
            $html = ob_get_clean();

            self::$logger->debug('<<loadTemplateFragment');
            return $html;
        } elseif (file_exists($defaultPath2)) {
            self::$logger->debug('Loading template ['.$defaultPath2.']');
            ob_start();
            require $defaultPath2;
            $html = ob_get_clean();

            self::$logger->debug('<<loadTemplateFragment');
            return $html;
        } else {
            self::$logger->debug('<<loadTemplateFragment');
            throw new IllegalArguementException('Template fragment not found in ['.$customPath.'] or ['.$defaultPath1.'] or ['.$defaultPath2.']!');
        }
    }

    /**
     * Enables you to set an explicit type of RendererProviderInterface implementation to use for rendering the records
     * attached to this view.
     *
     * @param string $ProviderClassName The name of the RendererProviderInterface implementation to use in this view object
     * @param string $acceptHeader      Optional pass the HTTP Accept header to select the correct renderer provider.
     *
     * @since 1.2
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public static function setProvider(string $ProviderClassName, string $acceptHeader = null): void
    {
        if ($ProviderClassName == 'auto') {
            $ProviderClassName = 'Alpha\View\Renderer\Html\RendererProviderHTML';

            if ($acceptHeader == 'application/json') {
                $ProviderClassName = 'Alpha\View\Renderer\Json\RendererProviderJSON';
            }

            self::$provider = ServiceFactory::getInstance($ProviderClassName, 'Alpha\View\Renderer\RendererProviderInterface');
        } else {
            if (class_exists($ProviderClassName)) {
                $provider = new $ProviderClassName();

                if ($provider instanceof RendererProviderInterface) {
                    self::$provider = ServiceFactory::getInstance($ProviderClassName, 'Alpha\View\Renderer\RendererProviderInterface');
                } else {
                    throw new IllegalArguementException('The provider class ['.$ProviderClassName.'] does not implement the RendererProviderInterface interface!');
                }
            } else {
                throw new IllegalArguementException('The provider class ['.$ProviderClassName.'] does not exist!');
            }
        }
    }

    /**
     * Get the current view renderer provider.
     *
     * @since 2.0
     */
    public static function getProvider(): \Alpha\View\Renderer\RendererProviderInterface
    {
        if (self::$provider instanceof RendererProviderInterface) {
            return self::$provider;
        } else {
            $config = ConfigProvider::getInstance();

            self::$provider = ServiceFactory::getInstance($config->get('app.renderer.provider.name'), 'Alpha\View\Renderer\RendererProviderInterface');

            return self::$provider;
        }
    }
}
