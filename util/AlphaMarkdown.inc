<?php

require_once $config->get('sysRoot').'alpha/lib/markdown/markdown.php';
require_once $config->get('sysRoot').'alpha/lib/geshi.php';
require_once $config->get('sysRoot').'alpha/view/widgets/Image.inc';

/**
 *
 * A custom version of the markdown class which uses the geshi library for rendering code
 * 
 * @package alpha::util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins 
 * @version $Id$
 * 
 */

class AlphaMarkdown extends MarkdownExtra_Parser {
	
	/**
	 * Custom version of the _doCodeBlocks_callback method which invokes a Gheshi 
	 * object to render code.
	 */
	public function _doCodeBlocks_callback($matches) {		
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
	 * @todo Update to use the new Image widget!
	 * 
	 */	
	public function _doImages_inline_callback($matches) {
		global $config;
		
		// just making sure that the controller is not the PDF one or preview one
		if(basename($_SERVER['PHP_SELF']) != 'ViewArticlePDF.php' && basename($_SERVER['PHP_SELF']) != 'PreviewArticle.php') {			
			// get the details of the source image
			$path = Image::convertImageURLToPath($matches[3]);
			$image_details = getimagesize($config->get('sysRoot').$path);
			$imgType = $image_details[2];
	    	if($imgType == 1)
	    		$type = 'gif';
	    	elseif($imgType == 2)
	    		$type = 'jpg';
	    	elseif($imgType == 3)
	    		$type = 'png';
	    		
			$img = new Image($path, $image_details[0], $image_details[1], $type, 0.95, false, false);
			$result = $img->renderHTMLLink();
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
	
	/**
	 * Custom _doAnchors_inline_callback that renders links to external sites with a
	 * target attribute and an icon.
	 */
	public function _doAnchors_inline_callback($matches) {
		global $config;
		
		$whole_match	=  $matches[1];
		$link_text		=  $this->runSpanGamut($matches[2]);
		$url			=  $matches[3];
		$title			=& $matches[6];
		
		$external = false;
		
		$parts = parse_url($url);

		/*
		 * Only an external link if:
		 * 
		 * 1. $url parses to a valid URL
		 * 2. $url has a host part
		 * 3. $url does not contain $config->get('sysURL'), i.e. points to a local resource.
		 */
		if(is_array($parts) && isset($parts['host']) && strpos($url, $config->get('sysURL')) === false)
			$external = true;			
		
		$url = $this->encodeAmpsAndAngles($url);

		$result = "<a href=\"$url\"";
		if (isset($title)) {
			$title = str_replace('"', '&quot;', $title);
			$title = $this->encodeAmpsAndAngles($title);
			$result .=  " title=\"$title\"";
		}
		if ($external) {
			$result .= " target=\"$url\"";
		}
		
		$link_text = $this->runSpanGamut($link_text);
		if (!$external) {
			$result .= ">$link_text</a>";
		}else{
			$result .= '>'.$link_text.'<img class="externalLink" src="'.$config->get('sysURL').'alpha/images/icons/page_go.png'.'" /></a>';
		}

		return $this->hashSpan($result);
	}
	
	/**
	 * Custom version of the _doTable_callback(...) method which sets the table border and CSS style
	 */
	public function _doTable_callback($matches) {
		$head		= $matches[1];
		$underline	= $matches[2];
		$content	= $matches[3];

		# Remove any tailing pipes for each line.
		$head		= preg_replace('/[|] *$/m', '', $head);
		$underline	= preg_replace('/[|] *$/m', '', $underline);
		$content	= preg_replace('/[|] *$/m', '', $content);
		
		# Reading alignement from header underline.
		$separators	= preg_split('/ *[|] */', $underline);
		foreach ($separators as $n => $s) {
			if (preg_match('/^ *-+: *$/', $s))		$attr[$n] = ' align="right"';
			else if (preg_match('/^ *:-+: *$/', $s))$attr[$n] = ' align="center"';
			else if (preg_match('/^ *:-+ *$/', $s))	$attr[$n] = ' align="left"';
			else									$attr[$n] = '';
		}
		
		# Creating code spans before splitting the row is an easy way to 
		# handle a code span containg pipes.
		$head	= $this->doCodeSpans($head);
		$headers	= preg_split('/ *[|] */', $head);
		$col_count	= count($headers);
		
		# Write column headers.
		$text = "<table class=\"bordered\" border=\"1\">\n";
		$text .= "<thead>\n";
		$text .= "<tr>\n";
		foreach ($headers as $n => $header)
			$text .= "  <th$attr[$n]>".$this->runSpanGamut(trim($header))."</th>\n";
		$text .= "</tr>\n";
		$text .= "</thead>\n";
		
		# Split content by row.
		$rows = explode("\n", trim($content, "\n"));
		
		$text .= "<tbody>\n";
		foreach ($rows as $row) {
			# Creating code spans before splitting the row is an easy way to 
			# handle a code span containg pipes.
			$row = $this->doCodeSpans($row);
			
			# Split row by cell.
			$row_cells = preg_split('/ *[|] */', $row, $col_count);
			$row_cells = array_pad($row_cells, $col_count, '');
			
			$text .= "<tr>\n";
			foreach ($row_cells as $n => $cell)
				$text .= "  <td$attr[$n]>".$this->runSpanGamut(trim($cell))."</td>\n";
			$text .= "</tr>\n";
		}
		$text .= "</tbody>\n";
		$text .= "</table>";
		
		return $this->hashBlock($text) . "\n";
	}
}

?>