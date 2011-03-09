<?php

require_once $config->get('sysRoot').'alpha/util/CatchError.inc';
require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/model/TagObject.inc';

/**
 * A widget for rendering a tag cloud, based off the TagObject instances in the
 * database.
 * 
 * @package alpha::view::widgets
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$
 * 
 */
class TagCloud {
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * A hash array of popular tags
	 * 
	 * @var array
	 */
	private $popTags = array();
	
	/**
	 * Constructor
	 */
	public function __construct($limit) {
		$this->popTags = TagObject::getPopularTagsArray($limit);
	}
	
	/**
	 * Render the tag cloud and return all of the HTML links in a single paragraph.
	 * 
	 * @param $minLinkSize The minimum font size for any tag link, in points.
	 * @param $maxLinkSize The maximum font size for any tag link, in points.
	 * @param $target The target attribute for the links 
	 * @return string
	 */
	public function render($minLinkSize=8, $maxLinkSize=20, $target='') {
		global $config;
		
		$html = '<p>';
		
		foreach (array_keys($this->popTags) as $key) {
			$linkSize = $this->popTags[$key];
			if ($linkSize < $minLinkSize)
				$linkSize = $minLinkSize;
			if ($linkSize > $maxLinkSize)
				$linkSize = $maxLinkSize;
			$html .= '<a href="'.$config->get('sysURL').'search/q/'.$key.'" style="font-size:'.$linkSize.'pt;"'.(empty($target) ? '' : ' target="'.$target.'"').' rel="tag">'.$key.'</a> ';
		}
		
		return $html.'</p>';
	}
}

?>