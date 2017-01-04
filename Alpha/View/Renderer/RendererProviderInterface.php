<?php

namespace Alpha\View\Renderer;

/**
 * Defines the renderer interface, which allows us to have various implementations (HTML,
 * JSON, XML etc.) behind one unified interface.  Use the
 * RendererProviderFactory::getInstance() method to get instances of this.
 *
 * @since 1.2
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
interface RendererProviderInterface
{
    /**
     * Provide the BO that we are going render.
     *
     * @param Alpha\Model\ActiveRecord $BO
     *
     * @since 1.2
     */
    public function setBO($BO);

    /**
     * Renders the create view for the BO using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @return string
     *
     * @since 1.2
     */
    public function createView($fields = array());

    /**
     * Renders the edit view for the BO using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @return string
     *
     * @since 1.2
     */
    public function editView($fields = array());

    /**
     * Renders the list view for the BO using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @return string
     *
     * @since 1.2
     */
    public function listView($fields = array());

    /**
     * Renders the detailed read-only view for the BO using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @return string
     *
     * @since 1.2
     */
    public function detailedView($fields = array());

    /**
     * Renders the admin view for the BO using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @return string
     *
     * @since 1.2
     */
    public function adminView($fields = array());

    /**
     * Renders the header content using the given renderer.
     *
     * @param Alpha\Controller\Controller $controller
     *
     * @return string
     *
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @since 1.2
     */
    public static function displayPageHead($controller);

    /**
     * Renders the footer content using the given renderer.
     *
     * @param Alpha\Controller\Controller $controller
     *
     * @return string
     *
     * @since 1.2
     */
    public static function displayPageFoot($controller);

    /**
     * Renders an update (e.g. successful save) message.
     *
     * @param string $message
     *
     * @return string
     *
     * @since 1.2
     */
    public static function displayUpdateMessage($message);

    /**
     * Renders an error (e.g. save failed) message.
     *
     * @param string $message
     *
     * @return string
     *
     * @since 1.2
     */
    public static function displayErrorMessage($message);

    /**
     * Renders an error page with the supplied HTTP error code and a message.
     *
     * @param string $code
     * @param string $message
     *
     * @return string
     *
     * @since 1.2
     */
    public static function renderErrorPage($code, $message);

    /**
     * Method to render a hidden HTML form for posting the OID of an object to be deleted.
     *
     * @param string $URI The URI that the form will point to
     *
     * @return string
     *
     * @since 1.2
     */
    public static function renderDeleteForm($URI);

    /**
     * Method to render a HTML form with two hidden, hashed (MD5) form fields to be used as
     * a check to ensure that a post to the controller is being sent from the same server
     * as hosting it.
     *
     * @return string
     *
     * @since 1.2
     */
    public static function renderSecurityFields();

    /**
     * Renders an Integer field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @return string
     *
     * @since 1.2
     */
    public function renderIntegerField($name, $label, $mode, $value = '');

    /**
     * Renders an Double field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @return string
     *
     * @since 1.2
     */
    public function renderDoubleField($name, $label, $mode, $value = '');

    /**
     * Renders an Boolean field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @return string
     *
     * @since 1.2
     */
    public function renderBooleanField($name, $label, $mode, $value = '');

    /**
     * Renders an Enum field value.
     *
     * @param string $name    The field name
     * @param string $label   The label to apply to the field
     * @param string $mode    The field mode (create/edit/view)
     * @param array  $options The Enum options
     * @param string $value   The field value (optional)
     *
     * @return string
     *
     * @since 1.0
     */
    public function renderEnumField($name, $label, $mode, $options, $value = '');

    /**
     * Renders an DEnum field value.
     *
     * @param string $name    The field name
     * @param string $label   The label to apply to the field
     * @param string $mode    The field mode (create/edit/view)
     * @param array  $options The DEnum options
     * @param string $value   The field value (optional)
     *
     * @return string
     *
     * @since 1.2
     */
    public function renderDEnumField($name, $label, $mode, $options, $value = '');

    /**
     * Method to render a field when type is not known.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @return string
     *
     * @since 1.2
     */
    public function renderDefaultField($name, $label, $mode, $value = '');

    /**
     * Renders a Text field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @return string
     *
     * @since 1.0
     */
    public function renderTextField($name, $label, $mode, $value = '');

    /**
     * Renders a String field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @return string
     *
     * @since 1.2.2
     */
    public function renderStringField($name, $label, $mode, $value = '');

    /**
     * Renders a Relation field value.
     *
     * @param string $name     The field name
     * @param string $label    The label to apply to the field
     * @param string $mode     The field mode (create/edit/view)
     * @param string $value    The field value (optional)
     * @param bool   $expanded Render the related fields in expanded format or not (optional)
     * @param bool   $buttons  Render buttons for expanding/contacting the related fields (optional)
     *
     * @return string
     *
     * @since 1.2
     */
    public function renderRelationField($name, $label, $mode, $value = '', $expanded = false, $buttons = true);

    /**
     * Convenience method that renders all fields for the current BO in edit/create/view mode.
     *
     * @param string $mode           (view|edit|create)
     * @param array  $filterFields   Optional list of field names to exclude from rendering.
     * @param array  $readOnlyFields Optional list of fields to render in a readonly fashion when rendering in create or edit mode.
     *
     * @return string
     *
     * @since 1.2
     */
    public function renderAllFields($mode, $filterFields = array(), $readOnlyFields = array());
}
