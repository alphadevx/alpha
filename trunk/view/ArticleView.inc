<?php

require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/markdown_facade.inc';

/**
 *
 * The rendering class for the article_object class
 * 
 * @package alpha::view
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class ArticleView extends View {	
	/**
	 * Method to generate the markdown HTML render of the article content
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function markdownView($fields=array()) {
		global $config;
		
		$markdown = new markdown_facade($this->BO);
		 
		$fields['markdownContent'] = $markdown->get_content();
		
		$html = '';
		// render edit and delete buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->getAccessLevel() == 'Admin') {
			// render edit buttons for admins only
			$html .= '&nbsp;&nbsp;';
			$button = new button("document.location = '".Front_Controller::generate_secure_URL('act=Edit&bo='.get_class($this->BO).'&oid='.$this->BO->getID())."'",'Edit','editBut');
			$html .= $button->render();
		}
			
		$fields['adminButtons'] = $html;
		
		$this->loadTemplate($this->BO, 'markdown', $fields);
	}
	
	/**
	 * Adds a note to the create article screen
	 */
	protected function after_createView_callback() {
		echo '<p><strong>Please note</strong> that you will only be able to attach files to the article once it has been created.</p><br>';
	}
	
	/**
	 * Renders a form to enable article editing with attachments options
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function editView($fields=array()) {
		if(method_exists($this, 'before_editView_callback'))
			$this->before_editView_callback();
		
		global $config;

		// the form action
		$fields['formAction'] = $_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"];
		
		// the form ID
		$fields['formID'] = get_class($this->BO).'_'.$this->BO->getID();
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('edit');
		
		// buffer HTML output for Create and Cancel buttons		
		$button = new button('submit', 'Save', 'saveBut');
		$fields['saveButton'] = $button->render();
		
		$button = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.getElementById('delete_form').submit();}", "Delete", "deleteBut");
		$fields['deleteButton'] = $button->render();
		
		$button = new button("document.location = '".Front_Controller::generate_secure_URL('act=ListAll&bo='.get_class($this->BO))."'", "Back to List", "cancelBut");
		$fields['cancelButton'] = $button->render();		
				
		// buffer security fields to $formSecurityFields variable		
		$fields['formSecurityFields'] = $this->renderSecurityFields();

		// OID will need to be posted for optimistic lock checking
		$fields['version_num'] = $this->BO->getVersionNumber();
		
		// file attachments section
		$fields['fileAttachments'] = $this->renderFileUploadSection();
		
		$this->loadTemplate($this->BO, 'edit', $fields);
		
		if(method_exists($this, 'after_editView_callback'))
			$this->after_editView_callback();
	}
	
	/**
	 * Renders the HTML for the file upload section
	 *
	 * @return string
	 */
	private function renderFileUploadSection() {		
		$html = '<tr><td colspan="2">&nbsp;</td></tr><tr><th colspan="2" style="text-align:left;">File Attachments:</th></tr>';
		
		if (is_dir($this->BO->get_attachments_location())) {
			$handle = opendir($this->BO->get_attachments_location());
	   		
	        // loop over the attachments directory
		    while (false !== ($file = readdir($handle))) {
		    	if($file != '.' && $file != '..') {
			    	$html .= '<tr><td>';		    	
					$html .= '&nbsp;'.$file.'&nbsp;<em>('.number_format(filesize($this->BO->get_attachments_location().'/'.$file)/1024).' KB)</em>';
					$html .= '</td>';
					$html .= '<td>';
					$temp = new button("if(confirm('Are you sure you want to delete the file ".$file."')) { document.forms[0]['file_to_delete'].value = '".$file."'; document.forms[0].submit();}", "Delete", "deleteBut");
					$html .= $temp->render();
					$html .= '</td></tr>';
		    	}
		    }
			
			$html .= '<tr><td>';
			$html .= 'Attachment file location';
			$html .= '</td>';
		}else{
			// we will take this opportunity to create the attachments folder is it does
			// not already exist.
			$this->BO->create_attachments_folder();
		}

		$html .= '<td>';
		$html .= '<input name="userfile" type="file" value="Browse..." size="70"/>';
		$html .= '</td></tr>';
		
		$html .= '<tr><td colspan="2">';
		$temp = new button('submit', 'Upload', 'uploadBut');
		$html .= $temp->render();
		$html .= '</td></tr>';
		
		$html .= '<input type="hidden" name="file_to_delete" value=""/>';
		
		return $html;
	}
}

?>