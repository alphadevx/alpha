<?php

// $Id$

require_once $sysRoot.'model/person_object.inc';
require_once $sysRoot.'view/View.inc';
require_once $sysRoot.'util/markdown_facade.inc';

/**
 *
 * The rendering class for the news_object class
 * 
 * @package Design-Ireland
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class news extends View
{
	/**
	 * the render mode for the object view, default is HTML
	 * @var string
	 */
	var $render_mode = 'HTML';

	/**
	 * constructor for the person	
	 * @param news_object $news_object the news business object for rendering
	 */
	function news($news_object)
	{
		$this->BO = $news_object;
	}
	
	/**
	 * method to generate the markdown HTML render of the news content
	 */
	function markdown_view() {
		$markdown = new markdown_facade($this->BO);
		 
		echo $markdown->get_content();
	}	
}

?>
