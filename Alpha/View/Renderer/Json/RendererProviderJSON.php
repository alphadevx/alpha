<?php

namespace Alpha\View\Renderer\Json;

use Alpha\View\Renderer\RendererProviderInterface;
use Alpha\Util\Logging\Logger;

/**
 * JSON renderer.
 *
 * @since 2.0
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
class RendererProviderJSON implements RendererProviderInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger;
     *
     * @since 2.0
     */
    private static $logger = null;

    /**
     * The active record that we are renderering.
     *
     * @var \Alpha\Model\ActiveRecord
     *
     * @since 2.0
     */
    private $record;

    /**
     * The constructor.
     *
     * @since 2.0
     */
    public function __construct()
    {
        self::$logger = new Logger('RendererProviderJSON');
        self::$logger->debug('>>__construct()');

        self::$logger->debug('<<__construct');
    }

    /**
     * {@inheritdoc}
     */
    public function setRecord($record): void
    {
        $this->record = $record;
    }

    /**
     * {@inheritdoc}
     */
    public function createView($fields = array()): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function editView($fields = array()): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function listView($fields = array()): string
    {
        self::$logger->debug('>>listView(fields=['.var_export($fields, true).'])');

        $json = json_encode($this->record->toArray()).',';

        self::$logger->debug('<<listView [JSON]');

        return $json;
    }

    /**
     * {@inheritdoc}
     */
    public function detailedView($fields = array()): string
    {
        self::$logger->debug('>>detailedView(fields=['.var_export($fields, true).'])');

        $json = json_encode($this->record->toArray());

        self::$logger->debug('<<detailedView [JSON]');

        return $json;
    }

    /**
     * {@inheritdoc}
     */
    public function adminView($fields = array()): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function displayPageHead($controller): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function displayPageFoot($controller): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function displayUpdateMessage($message): string
    {
        self::$logger->debug('>>displayUpdateMessage(fields=['.var_export($message, true).'])');

        $json = json_encode(array('message' => $message));

        self::$logger->debug('<<displayUpdateMessage [JSON]');

        return $json;
    }

    /**
     * {@inheritdoc}
     */
    public static function displayErrorMessage($message): string
    {
        self::$logger->debug('>>displayErrorMessage(fields=['.var_export($message, true).'])');

        $json = json_encode(array('message' => $message));

        self::$logger->debug('<<displayErrorMessage [JSON]');

        return $json;
    }

    /**
     * {@inheritdoc}
     */
    public static function renderErrorPage($code, $message): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function renderDeleteForm($URI): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function renderSecurityFields(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderIntegerField($name, $label, $mode, $value = ''): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderDoubleField($name, $label, $mode, $value = ''): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderBooleanField($name, $label, $mode, $value = ''): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderEnumField($name, $label, $mode, $options, $value = ''): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderDEnumField($name, $label, $mode, $options, $value = ''): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderDefaultField($name, $label, $mode, $value = ''): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderTextField($name, $label, $mode, $value = ''): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderStringField($name, $label, $mode, $value = ''): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderRelationField($name, $label, $mode, $value = '', $expanded = false, $buttons = true): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAllFields($mode, $filterFields = array(), $readOnlyFields = array()): string
    {
        self::$logger->debug('>>renderAllFields(mode=['.$mode.'], filterFields=['.var_export($filterFields, true).'], readOnlyFields=['.var_export($readOnlyFields, true).'])');

        $json = json_encode(array_diff($this->record->toArray(), $filterFields));

        self::$logger->debug('<<renderAllFields [JSON]');

        return $json;
    }
}
