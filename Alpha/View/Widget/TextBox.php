<?php

namespace Alpha\View\Widget;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\InputFilter;
use Alpha\Model\Type\Text;
use Alpha\Exception\IllegalArguementException;

/**
 * Text HTML input box custom widget
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
class TextBox
{
    /**
     * The text object that will be edited by this text box
     *
     * @var Alpha\Model\Type\Text
     * @since 1.0
     */
    public $textObject;

    /**
     * The data label for the text object
     *
     * @var string
     * @since 1.0
     */
    public $label;

    /**
     * The name of the HTML input box
     *
     * @var string
     * @since 1.0
     */
    public $name;

    /**
     * The amount of rows to display by default
     *
     * @var integer
     * @since 1.0
     */
    public $rows;

    /**
     * An optional additional idenitfier to append to the id of the text box where many are on one page
     *
     * @var integer
     * @since 1.0
     */
    public $identifier;

    /**
     * The constructor
     *
     * @param Alpha\Model\Type\Text $text The text object that will be edited by this text box.
     * @param string $label The data label for the text object.
     * @param string $name The name of the HTML input box.
     * @param integer $rows The display size (rows).
     * @param integer $identifier An additional idenitfier to append to the id of the text box.
     * @since 1.0
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function __construct($text, $label, $name, $rows=5, $identifier=0)
    {
        $config = ConfigProvider::getInstance();

        if ($text instanceof Text)
            $this->textObject = $text;
        else
            throw new IllegalArguementException('Text object passed ['.var_export($text, true).'] is not a valid Text object!');

        $this->label = $label;
        $this->rows = $rows;
        $this->identifier = $identifier;

        if ($config->get('security.encrypt.http.fieldnames'))
            $this->name = base64_encode(SecurityUtils::encrypt($name));
        else
            $this->name = $name;
    }

    /**
     * Renders the HTML and javascript for the text box
     *
     * @return string
     * @since 1.0
     */
    public function render()
    {
        $config = ConfigProvider::getInstance();

        $html = '<div class="form-group">';
        $html .= '  <label for="'.$this->name.'">'.$this->label.'</label>';

        $html .= '<textarea class="form-control" maxlength="'.$this->textObject->getSize().'" id="text_field_'.$this->name.'_'.$this->identifier.'" rows="'.$this->rows.'" name="'.$this->name.'">';

        if ($this->textObject->getAllowHTML())
            $html .= InputFilter::decode($this->textObject->getValue(), true);
        else
            $html .= InputFilter::decode($this->textObject->getValue());

        $html .= '</textarea>';

        $html .= '</div>';


        if ($this->textObject->getRule() != '') {
            $html .= '<input type="hidden" id="'.$this->name.'_msg" value="'.$this->textObject->getHelper().'"/>';
            $html .= '<input type="hidden" id="'.$this->name.'_rule" value="'.$this->textObject->getRule().'"/>';
        }

        return $html;
    }

    /**
     * Setter for text object
     *
     * @param string $text
     * @since 1.0
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function setTextObject($text)
    {
        if ($text instanceof Text)
            $this->text = $text;
        else
            throw new IllegalArguementException('Text object passed ['.var_export($text, true).'] is not a valid Text object!');
    }

    /**
     * Getter for text object
     *
     * @return Alpha\Model\Type\Text
     * @since 1.0
     */
    function getTextObject()
    {
        return $this->textObject;
    }
}

?>