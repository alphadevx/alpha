<?php

namespace Alpha\View;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Controller\Front\FrontController;
use Alpha\View\Widget\StringBox;
use Alpha\View\Widget\Button;
use Alpha\Model\Type\DEnumItem;

/**
 *
 * The rendering class for the DEnum class
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
class DEnumView extends View
{
    /**
     * Custom list view
     *
     * @return string
     * @since 1.0
     */
    public function listView()
    {
        $config = ConfigProvider::getInstance();

        $reflection = new \ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();
        $labels = $this->BO->getDataLabels();
        $colCount = 1;

        $html = '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST">';
        $html .= '<table class="table">';
        // first render all of the table headers
        $html .= '<tr>';
        foreach ($properties as $propObj) {
            $prop = $propObj->name;
            if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {
                if (get_class($this->BO->getPropObject($prop)) != 'Alpha\Model\Type\Text') {
                    $colCount ++;
                    $html .= '  <th>'.$labels[$prop].'</th>';
                }
            }
            if ($prop == 'OID')
                $html .= '  <th>'.$labels[$prop].'</th>';
        }
        // render the count
        $html .= '  <th>Item count</th>';

        $html .= '</tr><tr>';

        // and now the values
        foreach ($properties as $propObj) {
            $prop = $propObj->name;
            if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {
                if (get_class($this->BO->getPropObject($prop)) != 'Alpha\Model\Type\Text') {
                    $html .= '  <td>&nbsp;'.$this->BO->get($prop).'</td>';
                }
            }
            if ($prop == 'OID')
                $html .= '  <td>&nbsp;'.$this->BO->getID().'</td>';
        }
        // render the count
        $html .= '  <td>&nbsp;'.$this->BO->getItemCount().'</td>';

        $html .= '</tr>';

        $html .= '<tr><td colspan="'.($colCount+1).'" style="text-align:center;">';
        // render edit buttons for admins only
        if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
            $html .= '&nbsp;&nbsp;';
            $button = new Button("document.location = '".FrontController::generateSecureURL('act=EditDEnum&oid='.$this->BO->getOID())."'", "Edit", "edit".$this->BO->getOID()."But");
            $html .= $button->render();
        }
        $html .= '</td></tr>';

        $html .= '</table>';

        $html .= '</form>';

        return $html;
    }

    /**
     * Custom edit view
     *
     * @return string
     * @since 1.0
     */
    public function editView()
    {
        $config = ConfigProvider::getInstance();

        $labels = $this->BO->getDataLabels();
        $obj_type = '';

        $html = '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" accept-charset="UTF-8">';

        $temp = new StringBox($this->BO->getPropObject('name'), $labels['name'], 'name', '', 0, true, true);
        $html .= $temp->render();

        $html .= '<h3>DEnum display values:</h3>';

        // now get all of the options for the enum and render
        $denum = $this->BO;
        $tmp = new DEnumItem();
        $denumItems = $tmp->loadItems($denum->getID());

        foreach ($denumItems as $item) {
            $labels = $item->getDataLabels();
            $temp = new StringBox($item->getPropObject('value'), $labels['value'], 'value_'.$item->getID(), '');
            $html .= $temp->render();
        }

        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('version_num')) : 'version_num');

        $html .= '<input type="hidden" name="'.$fieldname.'" value="'.$this->BO->getVersion().'"/>';

        $html .= '<h3>Add a new value to the DEnum dropdown list:</h3>';

        $temp = new StringBox(new String(), 'Dropdown value', 'new_value', '');
        $html .= $temp->render();

        $temp = new Button('submit', 'Save', 'saveBut');
        $html .= $temp->render();
        $html .= '&nbsp;&nbsp;';
        $temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListDEnums')."'", 'Back to List', 'cancelBut');
        $html .= $temp->render();
        $html .= '';

        $html .= View::renderSecurityFields();

        $html .= '</form>';

        return $html;
    }
}

?>