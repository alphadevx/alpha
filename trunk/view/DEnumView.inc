<?php

require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 *
 * The rendering class for the DEnum class
 * 
 * @package alpha::view
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
class DEnumView extends AlphaView {
	/**
	 * Custom list view
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function listView() {
		global $config;

		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		$labels = $this->BO->getDataLabels();
		$colCount = 1;

		$html = '<table class="list_view">';
		$html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST">';
		// first render all of the table headers
		$html .= '<tr>';
		foreach($properties as $propObj) {
			$prop = $propObj->name;
			if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {
				if (get_class($this->BO->getPropObject($prop)) != 'Text') {
					$colCount ++;
					$html .= '	<th>'.$labels[$prop].'</th>';
				}
			}
			if ($prop == 'OID')
				$html .= '	<th>'.$labels[$prop].'</th>';			
		}
		// render the count
		$html .= '	<th>Item count</th>';
		
		$html .= '</tr><tr>';

		// and now the values
		foreach($properties as $propObj) {
			$prop = $propObj->name;
			if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {
				if (get_class($this->BO->getPropObject($prop)) != 'Text') {
					$html .= '	<td>&nbsp;'.$this->BO->get($prop).'</td>';
				}
			}
			if ($prop == 'OID')
				$html .= '	<td>&nbsp;'.$this->BO->getID().'</td>';
		}
		// render the count
		$html .= '	<td>&nbsp;'.$this->BO->getItemCount().'</td>';
		
		$html .= '</tr>';

		$html .= '<tr><td colspan="'.($colCount+1).'" align="center">';
		// render edit buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			$html .= '&nbsp;&nbsp;';
			$temp = new button("document.location = '".FrontController::generateSecureURL('act=EditDEnum&oid='.$this->BO->getID())."'", 'Edit', 'editBut');
			$html .= $temp->render();
		}
		$html .= '</td></tr>';

		$html .= '</form>';
		$html .= '</table>';
		
		return $html;
	}
	
	/**
	 * Custom edit view
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function editView() {		
		global $config;

		$labels = $this->BO->getDataLabels();
		$obj_type = '';

		$html = '<table cols="2" class="edit_view">';
		$html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST">';
		
		$temp = new StringBox($this->BO->getPropObject('name'), $labels['name'], 'name', '', 0, true, true);
		$html .= $temp->render();
		
		$html .= '<tr><td colspan="2"><h3>DEnum display values:</h3></td></tr>';
		
		// now get all of the options for the enum and render
		$denum = $this->BO;
		$tmp = new DEnumItem();
		$denumItems = $tmp->loadItems($denum->getID());						
		
		foreach ($denumItems as $item) {
			$labels = $item->getDataLabels();
			$temp = new StringBox($item->getPropObject('value'), $labels['value'], 'value_'.$item->getID(), '');
			$html .= $temp->render();
		}
		
		$html .= '<input type="hidden" name="version_num" value="'.$this->BO->getVersion().'"/>';
		
		$html .= '<tr><td colspan="2"><h3>Add a new value to the DEnum dropdown list:</h3></td></tr>';
		
		$temp = new StringBox(new String(), 'Dropdown value', 'new_value', '');
		$html .= $temp->render();
		
		$html .= '<tr><td colspan="2">';
		
		$temp = new button('submit', 'Save', 'saveBut');
		$html .= $temp->render();
		$html .= '&nbsp;&nbsp;';
		$temp = new button("document.location = '".FrontController::generateSecureURL('act=ListDEnums')."'", 'Back to List', 'cancelBut');
		$html .= $temp->render();
		$html .= '</td></tr>';

		$html .= AlphaView::renderSecurityFields();
		
		$html .= '</form></table>';
		
		return $html;
	}
}

?>