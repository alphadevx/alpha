<?php

// $Id: markdown_facade.inc 259 2007-03-03 20:47:13Z john $

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
}

?>
