<?php

require_once $config->get('sysRoot').'alpha/lib/markdown/markdown.php';
require_once $config->get('sysRoot').'alpha/view/widgets/Image.inc';

/**
 *
 * A custom version of the markdown class which uses the geshi library for rendering code
 * 
 * @package alpha::util
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class AlphaMarkdown extends MarkdownExtra_Parser {
	
	/**
	 * Custom version of the _doCodeBlocks_callback method which invokes a Gheshi 
	 * object to render code.
	 * 
	 * @since 1.0
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
		
		$geshiInstalled = true;
		
		try{
			fopen('geshi.php', 'r', true);
			require_once 'geshi.php';
		}catch (Exception $e) {
			$geshiInstalled = false;
		}
		
		if($geshiInstalled) {
			$geshi = new GeSHi($codeblock, $language);
			$codeblock = $geshi->parse_code();
		}
		
		$result = "\n\n".$this->hashBlock("<pre><code>" . $codeblock . "\n</code></pre>")."\n\n";	
		
		return $result;		
	}
	
	/**
	 * Custom _doAnchors_inline_callback that renders links to external sites with a
	 * target attribute and an icon.
	 * 
	 * @since 1.0
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
			$result .= '>'.$link_text.'<img src="'.$config->get('sysURL').'alpha/images/icons/page_go.png'.'" class="externalLink"/></a>';
		}

		return $this->hashSpan($result);
	}
	
	/**
	 * Custom version of the _doTable_callback(...) method which sets the table border and CSS style
	 * 
	 * @since 1.0
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