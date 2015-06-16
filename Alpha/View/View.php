<?php

namespace Alpha\View;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Model\ActiveRecord;
use Alpha\Exception\IllegalArguementException;
use Alpha\View\Renderer\RendererProviderFactory;
use ReflectionClass;

/**
 *
 * The master rendering view class for the Alpha Framework.
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
class View
{
    /**
     * The business object that will be rendered
     *
     * @var AlphaDAO
     * @since 1.0
     */
    protected $BO;

    /**
     * The rendering provider that will be used to render the business object
     *
     * @var AlphaRendererProviderInterface
     * @since 1.2
     */
    protected $provider;

    /**
     * Trace logger
     *
     * @var Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor for the View.  As this is protected, use the View::getInstance method from a public scope.
     *
     * @param Alpha\Model\ActiveRecord $BO
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    protected function __construct($BO)
    {
        self::$logger = new Logger('View');
        self::$logger->debug('>>__construct(BO=['.var_export($BO, true).'])');

        $config = ConfigProvider::getInstance();

        $class = new ReflectionClass($BO);
        $className = $class->getShortname();

        if (ActiveRecord::checkClassDefExists($className))
            $this->BO = $BO;
        else
            throw new IllegalArguementException('The BO provided ['.get_class($BO).'] is not defined anywhere!');

        $this->provider = RendererProviderFactory::getInstance($config->get('app.renderer.provider.name'), $this->BO);

        self::$logger->debug('<<__construct');
    }

    /**
     * Static method which returns a View object or a custom child view for the BO specified
     * if one exists
     *
     * @param ActiveRecord $BO The main business object that this view is going to render
     * @param boolean $returnParent Flag to enforce the return of this object instead of a child (defaults to false)
     * @return View Returns a View object, or a child view object if one exists for this BO
     * @since 1.0
     */
    public static function getInstance($BO, $returnParent=false)
    {
        if(self::$logger == null)
            self::$logger = new Logger('View');
        self::$logger->debug('>>getInstance(BO=['.var_export($BO, true).'], returnParent=['.$returnParent.'])');

        $config = ConfigProvider::getInstance();

        $class = new ReflectionClass($BO);
        $childView = $class->getShortname();
        $childView = $childView.'View';

        // Check to see if a custom view exists for this BO, and if it does return that view instead
        if (!$returnParent) {
            if (class_exists('\Alpha\View\\'.$childView)) {
                self::$logger->debug('<<getInstance [new '.'\Alpha\View\\'.$childView.'('.get_class($BO).')]');
                eval('$instance = new \Alpha\View\\'.$childView.'($BO);');
                return $instance;
            } elseif (class_exists('\View\\'.$childView)) {
                self::$logger->debug('<<getInstance [new '.'\View\\'.$childView.'('.get_class($BO).')]');
                eval('$instance = new \View\\'.$childView.'($BO);');
                return $instance;
            } else {
                self::$logger->debug('<<getInstance [new View('.get_class($BO).', true)]');
                return new View($BO, true);
            }
        } else {
            self::$logger->debug('<<getInstance [new View('.get_class($BO).', true)]');
            return new View($BO, true);
        }
    }

    /**
     * Simple setter for the view business object
     *
     * @param Alpha\Model\ActiveRecord $BO
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function setBO($BO)
    {
        self::$logger->debug('>>setBO(BO=['.var_export($BO, true).'])');

        if ($BO instanceof \Alpha\Model\ActiveRecord)
            $this->BO = $BO;
        else
            throw new IllegalArguementException('The BO provided ['.get_class($BO).'] is not defined anywhere!');

        self::$logger->debug('<<setBO');
    }

    /**
     * Gets the BO attached to this view (if any)
     *
     * @return Alpha\Model\ActiveRecord
     * @since 1.0
     */
    public function getBO()
    {
        return $this->BO;
    }

    /**
     * Renders the default create view
     *
     * @param array $fields Hash array of fields to pass to the template
     * @return string
     * @since 1.0
     */
    public function createView($fields=array())
    {
        self::$logger->debug('>>createView(fields=['.var_export($fields, true).'])');

        if(method_exists($this, 'before_createView_callback'))
            $this->before_createView_callback();

        $config = ConfigProvider::getInstance();

        $body = $this->provider->createView($fields);

        if(method_exists($this, 'after_createView_callback'))
            $this->after_createView_callback();

        self::$logger->debug('<<createView');

        return $body;
    }

    /**
     * Renders a form to enable object editing
     *
     * @param array $fields Hash array of fields to pass to the template
     * @return string
     * @since 1.0
     */
    public function editView($fields=array())
    {
        self::$logger->debug('>>editView(fields=['.var_export($fields, true).'])');

        if(method_exists($this, 'before_editView_callback'))
            $this->before_editView_callback();

        $config = ConfigProvider::getInstance();

        $body = $this->provider->editView($fields);

        if(method_exists($this, 'after_editView_callback'))
            $this->after_editView_callback();

        self::$logger->debug('<<editView');
        return $body;
    }

    /**
     * Renders the list view
     *
     * @param array $fields Hash array of fields to pass to the template
     * @return string
     * @since 1.0
     */
    public function listView($fields=array())
    {
        self::$logger->debug('>>listView(fields=['.var_export($fields, true).'])');

        if(method_exists($this, 'before_listView_callback'))
            $this->before_listView_callback();

        $config = ConfigProvider::getInstance();

        $body = $this->provider->listView($fields);

        if(method_exists($this, 'after_listView_callback'))
            $this->after_listView_callback();

        self::$logger->debug('<<listView');
        return $body;
    }

    /**
     * Renders a detailed view of the object (read-only)
     *
     * @param array $fields Hash array of fields to pass to the template
     * @return string
     * @since 1.0
     */
    public function detailedView($fields=array())
    {
        self::$logger->debug('>>detailedView(fields=['.var_export($fields, true).'])');

        if(method_exists($this, 'before_detailedView_callback'))
            $this->before_detailedView_callback();

        $config = ConfigProvider::getInstance();

        $body = $this->provider->detailedView($fields);

        if(method_exists($this, 'after_detailedView_callback'))
            $this->after_detailedView_callback();

        self::$logger->debug('<<detailedView');
        return $body;
    }

    /**
     * Renders the admin view for the business object screen
     *
     * @param array $fields Hash array of fields to pass to the template
     * @return string
     * @since 1.0
     */
    public function adminView($fields=array())
    {
        self::$logger->debug('>>adminView(fields=['.var_export($fields, true).'])');

        if(method_exists($this, 'before_adminView_callback'))
            $this->before_adminView_callback();

        $config = ConfigProvider::getInstance();

        $body = $this->provider->adminView($fields);

        if(method_exists($this, 'after_adminView_callback'))
            $this->after_adminView_callback();

        self::$logger->debug('<<adminView');
        return $body;
    }

    /**
     * Method to render the page header content
     *
     * @param Alpha\Controller\Controller $controller
     * @return string
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public static function displayPageHead($controller)
    {
        if (self::$logger == null)
            self::$logger = new Logger('View');
        self::$logger->debug('>>displayPageHead(controller=['.var_export($controller, true).'])');

        if (method_exists($controller, 'before_displayPageHead_callback'))
            $controller->before_displayPageHead_callback();

        $config = ConfigProvider::getInstance();

        $provider = RendererProviderFactory::getInstance($config->get('app.renderer.provider.name'));
        eval('$header = '.get_class($provider).'::displayPageHead($controller);');

        if (method_exists($controller, 'after_displayPageHead_callback'))
            $header.= $controller->after_displayPageHead_callback();

        self::$logger->debug('<<displayPageHead ['.$header.']');
        return $header;
    }

    /**
     * Method to render the page footer content
     *
     * @param Alpha\Aonctoller\Controller $controller
     * @return string
     * @since 1.0
     */
    public static function displayPageFoot($controller)
    {
        if (self::$logger == null)
            self::$logger = new Logger('View');

        self::$logger->debug('>>displayPageFoot(controller=['.get_class($controller).'])');

        $config = ConfigProvider::getInstance();

        $footer = '';

        if(method_exists($controller, 'before_displayPageFoot_callback'))
            $footer .= $controller->before_displayPageFoot_callback();

        $provider = RendererProviderFactory::getInstance($config->get('app.renderer.provider.name'));
        eval('$footer .= '.get_class($provider).'::displayPageFoot($controller);');

        if(method_exists($controller, 'after_displayPageFoot_callback'))
            $footer .= $controller->after_displayPageFoot_callback();

        self::$logger->debug('<<displayPageFoot ['.$footer.']');
        return $footer;
    }

    /**
     * Renders the content for an update (e.g. successful save) message
     *
     * @param string $message
     * @return string
     * @since 1.0
     */
    public static function displayUpdateMessage($message)
    {
        if(self::$logger == null)
            self::$logger = new Logger('View');
        self::$logger->debug('>>displayUpdateMessage(message=['.$message.'])');

        $config = ConfigProvider::getInstance();

        $provider = RendererProviderFactory::getInstance($config->get('app.renderer.provider.name'));
        eval('$message = '.get_class($provider).'::displayUpdateMessage($message);');

        self::$logger->debug('<<displayUpdateMessage ['.$message.']');
        return $message;
    }

    /**
     * Renders the content for an error (e.g. save failed) message
     *
     * @param string $message
     * @return string
     * @since 1.0
     */
    public static function displayErrorMessage($message)
    {
        if(self::$logger == null)
            self::$logger = new Logger('View');
        self::$logger->debug('>>displayErrorMessage(message=['.$message.'])');

        $config = ConfigProvider::getInstance();

        $provider = RendererProviderFactory::getInstance($config->get('app.renderer.provider.name'));
        eval('$message = '.get_class($provider).'::displayErrorMessage($message);');

        self::$logger->debug('<<displayErrorMessage ['.$message.']');
        return $message;
    }

    /**
     * Renders an error page with the supplied error code (typlically a HTTP code) and a message
     *
     * @param string $code
     * @param string $message
     * @return string
     * @since 1.0
     */
    public static function renderErrorPage($code, $message)
    {
        if(self::$logger == null)
            self::$logger = new Logger('View');
        self::$logger->debug('>>renderErrorPage(code=['.$code.'],message=['.$message.'])');

        $config = ConfigProvider::getInstance();

        $provider = RendererProviderFactory::getInstance($config->get('app.renderer.provider.name'));
        eval('$message = '.get_class($provider).'::renderErrorPage($code, $message);');

        self::$logger->debug('<<renderErrorPage ['.$message.']');
        return $message;
    }

    /**
     * Method to render a hidden HTML form for posting the OID of an object to be deleted
     *
     * @param string $URI The URI that the form will point to
     * @return string
     * @since 1.0
     */
    public static function renderDeleteForm($URI) {
        if(self::$logger == null)
            self::$logger = new Logger('View');
        self::$logger->debug('>>renderDeleteForm()');

        $config = ConfigProvider::getInstance();

        $provider = RendererProviderFactory::getInstance($config->get('app.renderer.provider.name'));
        eval('$html = '.get_class($provider).'::renderDeleteForm("'.$URI.'");');

        self::$logger->debug('<<renderDeleteForm ['.$html.']');
        return $html;
    }

    /**
     * Method to render a HTML form with two hidden, hashed (MD5) form fields to be used as
     * a check to ensure that a post to the controller is being sent from the same server
     * as hosting it.
     *
     * @return string
     * @since 1.0
     */
    public static function renderSecurityFields()
    {
        if(self::$logger == null)
            self::$logger = new Logger('View');
        self::$logger->debug('>>renderSecurityFields()');

        $config = ConfigProvider::getInstance();

        $provider = RendererProviderFactory::getInstance($config->get('app.renderer.provider.name'));
        eval('$html = '.get_class($provider).'::renderSecurityFields();');

        self::$logger->debug('<<renderSecurityFields ['.$html.']');
        return $html;
    }

    /**
     * Method to render the default Integer HTML
     *
     * @param string $name The field name
     * @param string $label The label to apply to the field
     * @param string $mode The field mode (create/edit/view)
     * @param string $value The field value (optional)
     * @param bool $tableTags Include table tags and label (optional)
     * @return string
     * @since 1.0
     */
    public function renderIntegerField($name, $label, $mode, $value = '', $tableTags = true)
    {
        self::$logger->debug('>>renderIntegerField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderIntegerField($name, $label, $mode, $value, $tableTags);

        self::$logger->debug('<<renderIntegerField ['.$html.']');
        return $html;
    }

    /**
     * Method to render the default Double HTML
     *
     * @param string $name The field name
     * @param string $label The label to apply to the field
     * @param string $mode The field mode (create/edit/view)
     * @param string $value The field value (optional)
     * @param bool $tableTags Include table tags and label (optional)
     * @return string
     * @since 1.0
     */
    public function renderDoubleField($name, $label, $mode, $value = '', $tableTags = true)
    {
        self::$logger->debug('>>renderDoubleField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderDoubleField($name, $label, $mode, $value, $tableTags);

        self::$logger->debug('<<renderDoubleField ['.$html.']');
        return $html;
    }

    /**
     * Method to render the default Boolean HTML
     *
     * @param string $name The field name
     * @param string $label The label to apply to the field
     * @param string $mode The field mode (create/edit/view)
     * @param string $value The field value (optional)
     * @param bool $tableTags Include table tags and label (optional)
     * @return string
     * @since 1.0
     */
    public function renderBooleanField($name, $label, $mode, $value = '', $tableTags = true)
    {
        self::$logger->debug('>>renderBooleanField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderBooleanField($name, $label, $mode, $value, $tableTags);

        self::$logger->debug('<<renderBooleanField ['.$html.']');
        return $html;
    }

    /**
     * Method to render the default Enum HTML
     *
     * @param string $name The field name
     * @param string $label The label to apply to the field
     * @param string $mode The field mode (create/edit/view)
     * @param array $options The Enum options
     * @param string $value The field value (optional)
     * @param bool $tableTags Include table tags and label (optional)
     * @return string
     * @since 1.0
     */
    public function renderEnumField($name, $label, $mode, $options, $value='', $tableTags=true)
    {
        self::$logger->debug('>>renderEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderEnumField($name, $label, $mode, $options, $value, $tableTags);

        self::$logger->debug('<<renderEnumField ['.$html.']');
        return $html;
    }

    /**
     * Method to render the default DEnum HTML
     *
     * @param string $name The field name
     * @param string $label The label to apply to the field
     * @param string $mode The field mode (create/edit/view)
     * @param array $options The DEnum options
     * @param string $value The field value (optional)
     * @param bool $tableTags Include table tags and label (optional)
     * @return string
     * @since 1.0
     */
    public function renderDEnumField($name, $label, $mode, $options, $value = '', $tableTags = true)
    {
        self::$logger->debug('>>renderDEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderDEnumField($name, $label, $mode, $options, $value, $tableTags);

        self::$logger->debug('<<renderDEnumField ['.$html.']');
        return $html;
    }

    /**
     * Method to render the default field HTML when type is not known
     *
     * @param string $name The field name
     * @param string $label The label to apply to the field
     * @param string $mode The field mode (create/edit/view)
     * @param string $value The field value (optional)
     * @param bool $tableTags Include table tags and label (optional)
     * @return string
     * @since 1.0
     */
    public function renderDefaultField($name, $label, $mode, $value = '', $tableTags = true)
    {
        self::$logger->debug('>>renderDefaultField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderDefaultField($name, $label, $mode, $value, $tableTags);

        self::$logger->debug('<<renderDefaultField ['.$html.']');
        return $html;
    }

    /**
     * render the default Text HTML
     *
     * @param string $name The field name
     * @param string $label The label to apply to the field
     * @param string $mode The field mode (create/edit/view)
     * @param string $value The field value (optional)
     * @param bool $tableTags Include table tags and label (optional)
     * @return string
     * @since 1.0
     */
    public function renderTextField($name, $label, $mode, $value = '', $tableTags = true)
    {
        self::$logger->debug('>>renderTextField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderTextField($name, $label, $mode, $value, $tableTags);

        self::$logger->debug('<<renderTextField ['.$html.']');
        return $html;
    }

    /**
     * render the default Relation HTML
     *
     * @param string $name The field name
     * @param string $label The label to apply to the field
     * @param string $mode The field mode (create/edit/view)
     * @param string $value The field value (optional)
     * @param bool $tableTags Include table tags and label (optional)
     * @param bool $expanded Render the related fields in expanded format or not (optional)
     * @param bool $buttons Render buttons for expanding/contacting the related fields (optional)
     * @return string
     * @since 1.0
     */
    public function renderRelationField($name, $label, $mode, $value = '', $tableTags = true, $expanded = false, $buttons = true)
    {
        self::$logger->debug('>>renderRelationField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'], expanded=['.$expanded.'], buttons=['.$buttons.'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderRelationField($name, $label, $mode, $value, $tableTags, $expanded, $buttons);

        self::$logger->debug('<<renderRelationField ['.$html.']');
        return $html;
    }

    /**
     * Renders all fields for the current BO in edit/create/view mode
     *
     * @param string $mode (view|edit|create)
     * @param array $filterFields Optional list of field names to exclude from rendering
     * @param array $readOnlyFields Optional list of fields to render in a readonly fashion when rendering in create or edit mode
     * @return string
     * @since 1.0
     */
    public function renderAllFields($mode, $filterFields = array(), $readOnlyFields = array())
    {
        self::$logger->debug('>>renderAllFields(mode=['.$mode.'], filterFields=['.var_export($filterFields, true).'], readOnlyFields=['.var_export($readOnlyFields, true).'])');

        $config = ConfigProvider::getInstance();

        $html = $this->provider->renderAllFields($mode, $filterFields, $readOnlyFields);

        self::$logger->debug('<<renderAllFields ['.$html.']');
        return $html;
    }

    /**
     * Loads a template for the BO specified if one exists.  Lower level custom templates
     * take precedence.
     *
     * @param Alpha\Model\ActiveRecord $BO
     * @param string $mode
     * @param array $fields
     * @return string
     * @since 1.0
     * @throws Alpha\Exception\IllegalArguementException
     */
    public static function loadTemplate($BO, $mode, $fields = array())
    {
        self::$logger->debug('>>loadTemplate(BO=['.var_export($BO, true).'], mode=['.$mode.'], fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // for each BO property, create a local variable holding its value
        $reflection = new ReflectionClass(get_class($BO));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if ($propName != 'logger' && !$propObj->isPrivate()) {
                $prop = $BO->getPropObject($propName);
                if ($prop instanceof DEnum) {
                    ${$propName} = $BO->getPropObject($propName)->getDisplayValue();
                } else {
                    ${$propName} = $BO->get($propName);
                }
            }
        }

        // loop over the $fields array and create a local variable for each key value
        foreach (array_keys($fields) as $fieldName)
            ${$fieldName} = $fields[$fieldName];

        $filename = $mode.'.phtml';
        $class = new ReflectionClass($BO);
        $className = $class->getShortname();

        $customPath = $config->get('app.root').'View/Html/Templates/'.$className.'/'.$filename;
        $defaultPath1 = $config->get('app.root').'Alpha/View/Renderer/Html/Templates/'.$className.'/'.$filename;
        $defaultPath2 = $config->get('app.root').'Alpha/View/Renderer/Html/Templates/'.$filename;

        // Check to see if a custom template exists for this BO, and if it does load that
        if (file_exists($customPath)) {
            self::$logger->debug('Loading template ['.$customPath.']');
            ob_start();
            require $customPath;
            $html = ob_get_clean();
            return $html;
        } elseif (file_exists($defaultPath1)) {
            self::$logger->debug('Loading template ['.$defaultPath1.']');
            ob_start();
            require $defaultPath1;
            $html = ob_get_clean();
            return $html;
        } elseif (file_exists($defaultPath2)) {
            self::$logger->debug('Loading template ['.$defaultPath2.']');
            ob_start();
            require $defaultPath2;
            $html = ob_get_clean();
            return $html;
        } else{
            throw new IllegalArguementException('No ['.$mode.'] HTML template found for class ['.$className.']');
        }

        self::$logger->debug('<<loadTemplate');
    }

    /**
     * Loads a template fragment from the Renderer/[type]/Fragments/[filename.ext] location.
     *
     * @param string $type Currently only html supported, later json and xml.
     * @param string $fileName The name of the fragment file
     * @param array $fields A hash array of field values to pass to the template fragment.
     * @return string
     * @since 1.2
     * @throws Alpha\Exception\IllegalArguementException
     */
    public static function loadTemplateFragment($type, $fileName, $fields = array())
    {
        if(self::$logger == null)
            self::$logger = new Logger('View');
        self::$logger->debug('>>loadTemplateFragment(type=['.$type.'], fileName=['.$fileName.'], fields=['.var_export($fields, true).'])');

        $config = ConfigProvider::getInstance();

        // loop over the $fields array and create a local variable for each key value
        foreach (array_keys($fields) as $fieldName)
            ${$fieldName} = $fields[$fieldName];

        $customPath = $config->get('app.root').'View/'.ucfirst($type).'/Fragments/'.$fileName;
        $defaultPath = $config->get('app.root').'Alpha/View/Renderer/'.ucfirst($type).'/Fragments/'.$fileName;

        // Check to see if a custom template exists for this BO, and if it does load that
        if (file_exists($customPath)) {
            self::$logger->debug('Loading template ['.$customPath.']');
            ob_start();
            require $customPath;
            $html = ob_get_clean();
            return $html;
        } elseif (file_exists($defaultPath)) {
            self::$logger->debug('Loading template ['.$defaultPath.']');
            ob_start();
            require $defaultPath;
            $html = ob_get_clean();
            return $html;
        } else {
            throw new IllegalArguementException('Template fragment not found in ['.$customPath.'] or ['.$defaultPath.']!');
        }

        self::$logger->debug('<<loadTemplateFragment');
    }

    /**
     * Enables you to set an explicit type of RendererProviderInterface implementation to use for rendering the business
     * object attached to this view.  Note that this has no affect on static methods of the View class, which always instantiate
     * a new RendererProviderInterface provider each time they're called.
     *
     * @param string $ProviderClassName The name of the AlphaRendererProviderInterface implementation to use in this view object
     * @since 1.2
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function setProvider($ProviderClassName)
    {
        $this->provider = RendererProviderFactory::getInstance($ProviderClassName, $this->BO);
    }
}

?>