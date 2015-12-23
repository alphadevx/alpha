<?php

namespace Alpha\View\Widget;

use Alpha\Exception\IllegalArguementException;
use Alpha\Model\Type\Date;
use Alpha\Model\Type\Timestamp;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Config\ConfigProvider;

/**
 * A HTML widget for rendering a text box with calendar icon for Date/Timestamp types.
 *
 * @since 1.0
 *
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
 */
class DateBox
{
    /**
     * The date or timestamp object for the widget.
     *
     * @var Alpha\Model\Type\Date or Alpha\Model\Type\Timestamp
     *
     * @since 1.0
     */
    private $dateObject = null;

    /**
     * The data label for the object.
     *
     * @var string
     *
     * @since 1.0
     */
    private $label;

    /**
     * The name of the HTML input box.
     *
     * @var string
     *
     * @since 1.0
     */
    private $name;

    /**
     * The constructor.
     *
     * @param Alpha\Model\Type\Date or Alpha\Model\Type\Timestamp $object The date or timestamp object that will be edited by this widget.
     * @param string                                              $label  The data label for the object.
     * @param string                                              $name   The name of the HTML input box.
     *
     * @since 1.0
     *
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function __construct($object, $label = '', $name = '')
    {
        $config = ConfigProvider::getInstance();

        // check the type of the object passed
        if ($object instanceof Date || $object instanceof Timestamp) {
            $this->dateObject = $object;
        } else {
            throw new IllegalArguementException('DateBox widget can only accept a Date or Timestamp object!');
        }

        $this->label = $label;

        if ($config->get('security.encrypt.http.fieldnames')) {
            $this->name = base64_encode(SecurityUtils::encrypt($name));
        } else {
            $this->name = $name;
        }
    }

    /**
     * Renders the text box and icon to open the calendar pop-up.
     *
     * @return string
     *
     * @since 1.0
     */
    public function render()
    {
        $config = ConfigProvider::getInstance();

        $html = '';

        /*
         * decide on the size of the text box and the height of the widget pop-up,
         * depending on the dateObject type
         */
        if (mb_strtoupper(get_class($this->dateObject)) == 'TIMESTAMP') {
            $size = 18;
            $cal_height = 230;
        } else {
            $size = 10;
            $cal_height = 230;
        }

        $value = $this->dateObject->getValue();

        if ($value == '0000-00-00') {
            $value = '';
        }

        $html = '<div class="form-group">';
        $html .= '  <label for="'.$this->name.'">'.$this->label.'</label>';
        $html .= '  <div class="input-group date">';
        $html .= '    <input type="text" class="form-control" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" readonly/>';
        $html .= '    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>';
        $html .= '  </div>';
        $html .= '</div>';

        $html .= '<script language="javascript">';
        $html .= 'if(window.jQuery) {';
        $html .= '  $(\'[Id="'.$this->name.'"]\').parent().datepicker({';
        $html .= '      format: "yyyy-mm-dd",';
        $html .= '      todayBtn: "linked",';
        $html .= '      todayHighlight: true,';
        $html .= '      autoclose: true';
        $html .= '  });';
        $html .= '}';
        $html .= '</script>';

        return $html;
    }
}
