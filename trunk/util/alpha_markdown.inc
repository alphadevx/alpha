<?php

// $Id$

require_once $config->get('sysRoot').'alpha/lib/markdown/markdown.php';
require_once $config->get('sysRoot').'alpha/lib/geshi.php';

/**
 *
 * A custom version of the markdown class which uses the geshi library for rendering code
 * 
 * @package Alpha Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins 
 * 
 */

class alpha_markdown extends MarkdownExtra_Parser {
	
	/**
	 * custom version of the _doCodeBlocks_callback method which invokes a heshi object to render code
	 */
	function _doCodeBlocks_callback($matches) {		
		$codeblock = $matches[1];	
		
		$codeblock = $this->outdent($codeblock);
		
		// trim leading newlines and trailing whitespace
		$codeblock = preg_replace(array('/\A\n+/', '/\n+\z/'), '', $codeblock);		
	
		// find the code block and replace it with a blank
		$codeTypeTag = array();		
		preg_match('/codeType=\[.*\]/', $codeblock, $codeTypeTag);
		$codeblock = preg_replace('/codeType=\[.*\]\n/', '', $codeblock);	
		
		if(isset($codeTypeTag[0])) {
			$start = strpos($codeTypeTag[0], '[');
			$end = strpos($codeTypeTag[0], ']');
			$language = substr($codeTypeTag[0], $start+1, $end-($start+1));			
		}else{
			// will use php as a defualt language type when none is provided
			$language = 'php';			
		}
		
		/*
		 * Create a GeSHi object
		 */
		$geshi = new GeSHi($codeblock, $language);
		
		/*
		 * And store the result!
		 */
		$codeblock = $geshi->parse_code();	
		
		$result = "\n\n".$this->hashBlock("<pre><code>" . $codeblock . "\n</code></pre>")."\n\n";	
		
		return $result;		
	}
	
	/**
	 * Custom _doImages_inline_callback() method that returns the Javascript for insertImage() when the 
	 * system is configured to do so, via sysCMSImagesWidget in the .ini, otherwise reverts to normal Markdown
	 * behaviour for handling images.
	 * 
	 */	
	function _doImages_inline_callback($matches) {
		global $config;
		
		// just making sure that the option is configured, and that the controller is not the PDF one
		if($config->get('sysCMSImagesWidget') && basename($_SERVER["PHP_SELF"]) != 'view_article_pdf.php') {			
			// get the details of the source image
			$url			= image::convertImageURLToPath($matches[3]);
			$image_details = getimagesize($config->get('sysRoot').$url);
			$imgType = $image_details[2];
	    	if($imgType == 1)
	    		$type = 'gif';
	    	elseif($imgType == 2)
	    		$type = 'jpg';
	    	elseif($imgType == 3)
	    		$type = 'png';
			
			$result = "<script language=\"javascript\">insertImage('$url', ".$image_details[0].", ".$image_details[1].", '$type', 0.95, 0, 1);</script>";
		}else{ // normal Markdown behaviour
			$whole_match	= $matches[1];
			$alt_text		= $matches[2];
			$url			= $matches[3];
			$title			=& $matches[6];
	
			$alt_text = str_replace('"', '&quot;', $alt_text);
			$result = "<img src=\"$url\" alt=\"$alt_text\"";
			if (isset($title)) {
				$title = str_replace('"', '&quot;', $title);
				$result .=  " title=\"$title\""; # $title already quoted
			}
			$result .= $this->empty_element_suffix;			
		}

		return $this->hashSpan($result);
	}
}

?>
