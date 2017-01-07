<?php

namespace Alpha\View\Widget;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Http\Request;
use Alpha\Model\Type\SmallText;
use Alpha\Exception\IllegalArguementException;

/**
 * String HTML input box custom widget.
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
class SmallTextBox
{
    /**
     * The string object that will be edited by this string box.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    public $stringObject;

    /**
     * The data label for the string object.
     *
     * @var string
     *
     * @since 1.0
     */
    public $label;

    /**
     * The name of the HTML input box.
     *
     * @var string
     *
     * @since 1.0
     */
    public $name;

    /**
     * The display size of the input box.
     *
     * @var int
     *
     * @since 1.0
     */
    public $size;

    /**
     * The constructor.
     *
     * @param \Alpha\Model\Type\SmallText $string The string object that will be edited by this text box.
     * @param string                  $label  The data label for the string object.
     * @param string                  $name   The name of the HTML input box.
     * @param int                     $size   The display size (characters).
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function __construct($string, $label, $name, $size = 0)
    {
        $config = ConfigProvider::getInstance();

        if ($string instanceof SmallText) {
            $this->stringObject = $string;
        } else {
            throw new IllegalArguementException('String object passed ['.var_export($string, true).'] is not a valid String object!');
        }

        $this->label = $label;
        $this->size = $size;

        if ($config->get('security.encrypt.http.fieldnames')) {
            $this->name = base64_encode(SecurityUtils::encrypt($name));
        } else {
            $this->name = $name;
        }
    }

    /**
     * Renders the HTML and javascript for the string box.
     *
     * @param bool $readOnly set to true to make the text box readonly (defaults to false)
     *
     * @return string
     *
     * @since 1.0
     */
    public function render($readOnly = false)
    {
        $request = new Request(array('method' => 'GET'));

        $html = '<div class="form-group">';
        $html .= '  <label for="'.$this->name.'">'.$this->label.'</label>';
        $html .= '  <input '.($this->stringObject->checkIsPassword() ? 'type="password"' : 'type="text"').($this->size == 0 ? ' style="width:100%;"' : ' size="'.$this->size.'"').' maxlength="'.SmallText::MAX_SIZE.'" name="'.$this->name.'" id="'.$this->name.'" value="'.(($request->getParam($this->name, false) && $this->stringObject->getValue() == '' && !$this->stringObject->checkIsPassword()) ? $request->getParam($this->name) : $this->stringObject->getValue()).'" class="form-control"'.($readOnly ? ' disabled="disabled"' : '').'/>';

        if ($this->stringObject->getRule() != '') {
            $html .= '  <input type="hidden" id="'.$this->name.'_msg" value="'.$this->stringObject->getHelper().'"/>';
            $html .= '  <input type="hidden" id="'.$this->name.'_rule" value="'.$this->stringObject->getRule().'"/>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Setter for string object.
     *
     * @param \Alpha\Model\Type\SmallText $string
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function setStringObject($string)
    {
        if ($string instanceof SmallText) {
            $this->stringObject = $string;
        } else {
            throw new IllegalArguementException('String object passed ['.var_export($string, true).'] is not a valid String object!');
        }
    }

    /**
     * Getter for string object.
     *
     * @return \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    public function getStringObject()
    {
        return $this->stringObject;
    }
}
