<?php

require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
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
class ArticleView extends View {	
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
			$this->BO->createAttachmentsFolder();
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
	
	/**
	 * Custom method to render the page header HTML content with CMS headers and onload events
	 * 
	 * @param Controller $controller
	 * @param string $cssStyleSheet
	 * @return string
	 * @todo remove output buffering around form_validator instance
	 */
	public function displayArticlePageHead($controller, $cssStyleSheet='') {
		if(method_exists($controller, 'before_displayPageHead_callback'))
			$controller->before_displayPageHead_callback();
		
		global $config;
		
		$html = '';
		
		$html.= '<html>';
		$html.= '<head>';
		$html.= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		$html.= '<title>'.$controller->getTitle().'</title>';
		$html.= '<meta name="Keywords" content="'.$controller->getKeywords().'">';
		$html.= '<meta name="Description" content="'.$controller->getDescription().'">';
		$html.= '<meta name="identifier" content="http://'.$config->get('sysURL').'/">';
		$html.= '<meta name="revisit-after" content="7 days">';
		$html.= '<meta name="expires" content="never">';
		$html.= '<meta name="language" content="en">';
		$html.= '<meta name="distribution" content="global">';
		$html.= '<meta name="title" content="'.$controller->getTitle().'">';
		$html.= '<meta name="robots" content="index,follow">';
		$html.= '<meta http-equiv="imagetoolbar" content="no">';

		if($cssStyleSheet == '')
			$cssStyleSheet = $config->get('sysTheme');
			
		$html.= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'/config/css/'.$cssStyleSheet.'.css.php">';

		$html.= '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
		
		ob_start();
		require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
		require_once $config->get('sysRoot').'alpha/view/widgets/image.js.php';
		$html.= ob_get_clean();
		
		// if we are working with a BO, render form validation Javascript rules
		if ($controller->getBO() != null) {
			require_once $config->get('sysRoot').'alpha/view/widgets/StringBox.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/TextBox.js.php';
		
			ob_start();
			require_once $config->get('sysRoot').'alpha/view/widgets/form_validator.js.php';
			$html.= ob_get_clean();

			$html.= '<script type="text/javascript">';
			ob_start();
			$validator = new form_validator($controller->getBO());
			$html.= ob_get_clean();
			$html.= '</script>';
		}
		
		if(method_exists($controller, 'during_displayPageHead_callback'))
			$html.= $controller->during_displayPageHead_callback();
		
		$html.= '</head>';
		$html.= '<body'.($this->BO->get('bodyOnload') != '' ? ' onload="'.$this->BO->get('bodyOnload').'"' : '').'>';
		
		if($config->get('sysCMSDisplayStandardHeader')) {
			$html.= '<p><a href="'.$config->get('sysURL').'">'.$config->get('sysTitle').'</a> &nbsp; &nbsp;';
			$denum = $this->BO->getPropObject('section');
			$html.= 'Site Section: <em>'.$denum->getDisplayValue().'</em> &nbsp; &nbsp;';
			$html.= 'Date Added: <em>'.$this->BO->getCreateTS()->getDate().'</em> &nbsp; &nbsp;';
			$html.= 'Last Updated: <em>'.$this->BO->getUpdateTS()->getDate().'</em> &nbsp; &nbsp;';
			$html.= 'Revision: <em>'.$this->BO->getVersion().'</em></p>';
		}
		
		$html.= $config->get('sysCMSHeader');
			
		$html.= '<h1>'.$controller->getTitle().'</h1>';
		
		if (isset($_SESSION['currentUser'])) {	
			$html.= '<p>You are logged in as '.$_SESSION['currentUser']->getDisplayname().'.  <a href="'.$config->get('sysURL').'alpha/controller/Logout.php">Logout</a></p>';
		}else{
			$html.= '<p>You are not logged in</p>';
		}
		
		if($controller->getStatus() != '')
			$html .= $controller->getStatus();
		
		if(method_exists($controller, 'after_displayPageHead_callback'))
			$html.= $controller->after_displayPageHead_callback();
			
		return $html;
	}
}

?>