<?php

require_once $config->get('sysRoot').'alpha/model/article_comment_object.inc';
require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/util/MarkdownFacade.inc';
require_once $config->get('sysRoot').'alpha/util/InputFilter.inc';

/**
 *
 * The rendering class for the article_comment_object class
 * 
 * @package alpha::view
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class ArticleCommentView extends AlphaView {	
	/**
	 * method to generate the markdown HTML render of the ArticleComment content
	 */
	public function markdownView() {
		global $config;
		
		$markdown = new MarkdownFacade($this->BO);
		$author = new person_object();
		$id = $this->BO->getCreatorID();
		$author->load($id->getValue());
		
		echo '<blockquote class="usercomment">';
		
		$create_TS = $this->BO->getCreateTS();
		$update_TS = $this->BO->getUpdateTS();

		echo '<p>Posted by '.($author->get('URL') == ''? $author->get('displayname') : '<a href="'.$author->get('URL').'" target="new window">'.$author->get('displayname').'</a>').' at '.$create_TS->getValue().'.';
		echo '&nbsp;'.$author->get('displayname').' has posted ['.$author->getCommentCount().'] comments on articles since joining.';
		echo '</p>';
		if($config->get('sysCMSCommentsAllowed') && isset($_SESSION['currentUser']) && $_SESSION['currentUser']->getID() == $author->getID())
			$this->editView();
		else
			echo $markdown->getContent();
		
		if($create_TS->getValue() != $update_TS->getValue()) {
			$updator = new person_object();
			$id = $this->BO->getCreatorID();
			$updator->load($id->getValue());
			echo '<p>Updated by '.($updator->get('URL') == ''? $updator->get('displayname') : '<a href="'.$updator->get('URL').'" target="new window">'.$updator->get('displayname').'</a>').' at '.$update_TS->getValue().'.</p>';
		}
		echo '</blockquote>';
	}
	
	/**
	 * Renders the custom create view
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function createView($fields=array()) {
		echo '<h2>Post a new comment:</h2>';

		echo '<table cols="2" class="create_view">';
		echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST">';
		
		$textBox = new textBox($this->BO->getPropObject('content'), $this->BO->getDataLabel('content'), 'content', '', 10);
		echo $textBox->render();		
		
		echo '<input type="hidden" name="article_oid" value="'.$this->BO->get('article_oid').'"/>';
		echo '<tr><td colspan="2">';
		
		$button = new button('submit', "Post Comment", 'createBut');
		echo $button->render();
			
		echo '</td></tr>';

		echo AlphaView::renderSecurityFields();
		
		echo '</form></table>';
		echo '<p class="warning">Please note that any comment you post may be moderated for spam or offensive material.</p>';
	}
	
	/**
	 * Custom edit view
	 * 
	 * @param array $fields hash array of HTML fields to pass to the template
	 */
	public function editView($fields=array()) {
		global $config;

		echo '<table cols="2" class="edit_view" style="width:100%; margin:0px">';
		echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST">';
		
		$textBox = new TextBox($this->BO->getPropObject('content'), $this->BO->getDataLabel('content'), 'content', '', 5, $this->BO->getID());
		echo $textBox->render();		

		echo '<input type="hidden" name="version_num" value="'.$this->BO->getVersion().'"/>';
		echo '<input type="hidden" name="article_comment_id" value="'.$this->BO->getID().'"/>';
		
		// render special buttons for admins only
		if ($_SESSION['currentUser']->inGroup('Admin') && strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
			echo '<tr><td colspan="2">';
			
			$temp = new button('submit', 'Save', 'saveBut');
			echo $temp->render();
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.forms[0].submit();}", 'Delete', 'deleteBut');
			echo $temp->render();
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",'Back to List','cancelBut');
			echo $temp->render();
			echo '</td></tr>';
			
			echo AlphaView::renderSecurityFields();
		
			echo '</form></table>';
		}else{
			echo '</table>';
			
			echo '<div align="center">';
			$temp = new button('submit', 'Update Your Comment', 'saveBut');
			echo $temp->render();
			echo '</div>';
			
			echo AlphaView::renderSecurityFields();
			
			echo '</form>';
		}		
	}
}

?>