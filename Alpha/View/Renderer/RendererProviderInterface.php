<?php

namespace Alpha\View\Renderer;

/**
 * Defines the renderer interface, which allows us to have various implementations (HTML,
 * JSON, XML etc.) behind one unified interface.  Use the
 * ServiceFactory::getInstance() method to get instances of this.
 *
 * @since 1.2
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
interface RendererProviderInterface
{
    /**
     * Provide the Record that we are going render.
     *
     * @param \Alpha\Model\ActiveRecord $Record
     *
     * @since 1.2
     */
    public function setRecord(\Alpha\Model\ActiveRecord $Record): void;

    /**
     * Renders the create view for the Record using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @since 1.2
     */
    public function createView(array $fields = array()): string;

    /**
     * Renders the edit view for the Record using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @since 1.2
     */
    public function editView(array $fields = array()): string;

    /**
     * Renders the list view for the Record using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @since 1.2
     */
    public function listView(array $fields = array()): string;

    /**
     * Renders the detailed read-only view for the Record using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @since 1.2
     */
    public function detailedView(array $fields = array()): string;

    /**
     * Renders the admin view for the Record using the selected renderer.
     *
     * @param array $fields Hash array of fields to pass to the template.
     *
     * @since 1.2
     */
    public function adminView(array $fields = array()): string;

    /**
     * Renders the header content using the given renderer.
     *
     * @param \Alpha\Controller\Controller $controller
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.2
     */
    public static function displayPageHead(\Alpha\Controller\Controller $controller): string;

    /**
     * Renders the footer content using the given renderer.
     *
     * @param \Alpha\Controller\Controller $controller
     *
     * @since 1.2
     */
    public static function displayPageFoot(\Alpha\Controller\Controller $controller): string;

    /**
     * Renders an update (e.g. successful save) message.
     *
     * @param string $message
     *
     * @since 1.2
     */
    public static function displayUpdateMessage(string $message): string;

    /**
     * Renders an error (e.g. save failed) message.
     *
     * @param string $message
     *
     * @since 1.2
     */
    public static function displayErrorMessage(string $message): string;

    /**
     * Renders an error page with the supplied HTTP error code and a message.
     *
     * @param string $code
     * @param string $message
     *
     * @since 1.2
     */
    public static function renderErrorPage(string $code, string $message): string;

    /**
     * Method to render a hidden HTML form for posting the ID of an object to be deleted.
     *
     * @param string $URI The URI that the form will point to
     *
     * @since 1.2
     */
    public static function renderDeleteForm(string $URI): string;

    /**
     * Method to render a HTML form with two hidden, hashed (MD5) form fields to be used as
     * a check to ensure that a post to the controller is being sent from the same server
     * as hosting it.
     *
     * @since 1.2
     */
    public static function renderSecurityFields(): string;

    /**
     * Renders an Integer field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @since 1.2
     */
    public function renderIntegerField(string $name, string $label, string $mode, string $value = ''): string;

    /**
     * Renders an Double field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @since 1.2
     */
    public function renderDoubleField(string $name, string $label, string $mode, string $value = ''): string;

    /**
     * Renders an Boolean field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @since 1.2
     */
    public function renderBooleanField(string $name, string $label, string $mode, string $value = ''): string;

    /**
     * Renders an Enum field value.
     *
     * @param string $name    The field name
     * @param string $label   The label to apply to the field
     * @param string $mode    The field mode (create/edit/view)
     * @param array  $options The Enum options
     * @param string $value   The field value (optional)
     *
     * @since 1.0
     */
    public function renderEnumField(string $name, string $label, string $mode, array $options, string $value = ''): string;

    /**
     * Renders an DEnum field value.
     *
     * @param string $name    The field name
     * @param string $label   The label to apply to the field
     * @param string $mode    The field mode (create/edit/view)
     * @param array  $options The DEnum options
     * @param string $value   The field value (optional)
     *
     * @since 1.2
     */
    public function renderDEnumField(string $name, string $label, string $mode, array $options, string $value = ''): string;

    /**
     * Method to render a field when type is not known.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @since 1.2
     */
    public function renderDefaultField(string $name, string $label, string $mode, string $value = ''): string;

    /**
     * Renders a Text field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @since 1.0
     */
    public function renderTextField(string $name, string $label, string $mode, string $value = ''): string;

    /**
     * Renders a String field value.
     *
     * @param string $name  The field name
     * @param string $label The label to apply to the field
     * @param string $mode  The field mode (create/edit/view)
     * @param string $value The field value (optional)
     *
     * @since 1.2.2
     */
    public function renderStringField(string $name, string $label, string $mode, string $value = ''): string;

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
     * @since 1.2
     */
    public function renderRelationField(string $name, string $label, string $mode, string $value = '', bool $expanded = false, bool $buttons = true): string;

    /**
     * Convenience method that renders all fields for the current Record in edit/create/view mode.
     *
     * @param string $mode           (view|edit|create)
     * @param array  $filterFields   Optional list of field names to exclude from rendering.
     * @param array  $readOnlyFields Optional list of fields to render in a readonly fashion when rendering in create or edit mode.
     *
     * @since 1.2
     */
    public function renderAllFields(string $mode, array $filterFields = array(), array $readOnlyFields = array()): string;
}
