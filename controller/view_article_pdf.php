<?php

// $Id: view_article_pdf.php 238 2007-02-03 22:36:54Z john $

require_once '../../config/config.conf';
require_once $sysRoot.'alpha/util/fpdf_facade.inc';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/model/article_object.inc';

/**
* 
* Controller used to display PDF version of an article where the title is provided in GET vars
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha CMS
* @copyright 2007 John Collins
*
*/
class view_article_pdf extends Controller
{								
	/**
	 * constructor that renders the page	
	 */
	function view_article_pdf() {
		global $sysTheme;
		
		// ensure that a title is provided
		if (isset($_GET["title"])) {
			$title = $_GET["title"];
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the article as a title was not supplied!','GET','other');
			exit;
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$article = new article_object();
		$article->load_by_title($title);
		
		$pdf = new fpdf_facade($article);		
	}	
}

// now build the new controller
$controller = new view_article_pdf();

?>