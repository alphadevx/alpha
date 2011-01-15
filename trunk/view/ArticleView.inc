<?php

require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/util/MarkdownFacade.inc';

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
class ArticleView extends AlphaView {	
	/**
	 * Method to generate the markdown HTML render of the article content
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function markdownView($fields=array()) {
		global $config;
		
		$markdown = new MarkdownFacade($this->BO);
		 
		$fields['markdownContent'] = $markdown->getContent();
		
		$this->loadTemplate($this->BO, 'markdown', $fields);
	}
	
	/**
	 * Adds a note to the create article screen
	 */
	protected function after_createView_callback() {
		echo '<p><strong>Please note</strong> that you will only be able to attach files to the article once it has been created.</p><br>';
	}
	
	/**
	 * Renders the list view (adds the dateAdded field for the list template)
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function listView($fields=array()) {
		$fields['dateAdded'] = $this->BO->getCreateTS()->getDate();
		parent::listView($fields);				
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
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// the form ID
		$fields['formID'] = get_class($this->BO).'_'.$this->BO->getID();
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('edit');
		
		// buffer HTML output for Create and Cancel buttons		
		$button = new button('submit', 'Save', 'saveBut');
		$fields['saveButton'] = $button->render();
		
		$js = "$('#dialogDiv').text('Are you sure you wish to delete this item?');
				$('#dialogDiv').dialog({
				buttons: {
					'OK': function(event, ui) {						
						$('#delete_oid').attr('value', '".$this->BO->getOID()."');
						$('#delete_form').submit();
					},
					'Cancel': function(event, ui) {
						$(this).dialog('close');
					}
				}
			})
			$('#dialogDiv').dialog('open');
			return false;";
		$button = new Button($js, "Delete", "deleteBut");
		$fields['deleteButton'] = $button->render();
		
		$button = new button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'", "Back to List", "cancelBut");
		$fields['cancelButton'] = $button->render();
		
		$tags = $this->BO->getPropObject('tags')->getRelatedObjects();
			
		if(count($tags) > 0) {
			$button = new button("document.location = '".FrontController::generateSecureURL('act=EditTags&bo='.get_class($this->BO).'&oid='.$this->BO->getOID())."'", "Edit Tags", "tagsBut");
			$fields['tagsButton'] = $button->render();
		}
				
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
	protected function renderFileUploadSection() {		
		$html = '<tr><td colspan="2">&nbsp;</td></tr><tr><th colspan="2" style="text-align:left;">File Attachments:</th></tr>';
		
		if (is_dir($this->BO->getAttachmentsLocation())) {
			$handle = opendir($this->BO->getAttachmentsLocation());
	   		
	        // loop over the attachments directory
		    while (false !== ($file = readdir($handle))) {
		    	if($file != '.' && $file != '..') {
			    	$html .= '<tr><td>';		    	
					$html .= '&nbsp;'.$file.'&nbsp;<em>('.number_format(filesize($this->BO->getAttachmentsLocation().'/'.$file)/1024).' KB)</em>';
					$html .= '</td>';
					$html .= '<td>';
					$js = "$('#dialogDiv').text('Are you sure you want to delete the file ".$file."');
							$('#dialogDiv').dialog({
							buttons: {
								'OK': function(event, ui) {						
									$('#file_to_delete').attr('value', '".$file."');
									$('#".get_class($this->BO).'_'.$this->BO->getID()."').submit();
								},
								'Cancel': function(event, ui) {
									$(this).dialog('close');
								}
							}
						})
						$('#dialogDiv').dialog('open');
						return false;";
					$button = new Button($js, "Delete", "deleteBut");
					$html .= $button->render();
					$html .= '</td></tr>';
		    	}
		    }
			
			$html .= '<tr><td>';
			$html .= 'Attachment file location';
			$html .= '</td>';
		}else{
			// we will take this opportunity to create the attachments folder is it does
			// not already exist.
			$this->BO->createAttachmentsFolder();
		}

		$html .= '<td>';
		$html .= '<input name="userfile" type="file" value="Browse..." size="70"/>';
		$html .= '</td></tr>';
		
		$html .= '<tr><td colspan="2">';
		$temp = new button('submit', 'Upload', 'uploadBut');
		$html .= $temp->render();
		$html .= '</td></tr>';
		
		$html .= '<input type="hidden" name="file_to_delete" id="file_to_delete" value=""/>';
		
		return $html;
	}
}

?>