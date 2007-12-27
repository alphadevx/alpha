<?php

// $Id: markdown_facade.inc 259 2007-03-03 20:47:13Z john $

require_once $config->get('sysRoot').'alpha/lib/markdown/markdown.php';
require_once $config->get('sysRoot').'alpha/lib/fpdf/fpdf.php';

/**
 *
 * A custom version of the markdown class which converts markdown content to a PDF using FPDF
 * 
 * @package Alpha Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins 
 * 
 */

class alpha_markdown2pdf extends MarkdownExtra_Parser {
	/**
	 * the PDF data returned by this Markdown parser
	 * @var pdf
	 */
	var $pdf = null;
	
	function alpha_markdown2pdf() {
		define('FPDF_FONTPATH','font/');
		$this->pdf = new FPDF();		
		$this->pdf->AddPage();
		
		
		#
	# Constructor function. Initialize the parser object.
	#
		# Add extra escapable characters before parent constructor 
		# initialize the table.
		$this->escape_chars .= ':|';
		
		# Insert extra document, block, and span transformations. 
		# Parent constructor will do the sorting.
		$this->document_gamut += array(
			"stripFootnotes"     => 15,
			"stripAbbreviations" => 25,
			"appendFootnotes"    => 50,
			);
		$this->block_gamut += array(
			"doTables"           => 15,
			"doDefLists"         => 45,
			);
		$this->span_gamut += array(
			"doFootnotes"        =>  4,
			"doAbbreviations"    =>  5,
			);
		
		parent::Markdown_Parser();
	}
	
	function _doHeaders_callback_setext_h1($matches) {
		//echo "_doHeaders_callback_setext_h1";
		//$attr  = $this->_doHeaders_attr($id =& $matches[2]);
		//$block = "<h1$attr>".$this->runSpanGamut($matches[1])."</h1>";
		//return "\n" . $this->hashBlock($block) . "\n\n";
		$this->pdf->SetFont('Arial','B',15);
	    //Move to the right
	    $this->pdf->Cell(80);
	    //Title
	    $this->pdf->Cell(30,10,$this->runSpanGamut($matches[1]),1,0,'C');
	    //Line break
	    $this->pdf->Ln(20);		
	}
	
	function formParagraphs($text) {
	#
	#	Params:
	#		$text - string to process with html <p> tags
	#
		# Strip leading and trailing lines:
		$text = preg_replace(array('/\A\n+/', '/\n+\z/'), '', $text);
		
		$grafs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

		#
		# Wrap <p> tags and unhashify HTML blocks
		#
		foreach ($grafs as $key => $value) {
			$value = trim($this->runSpanGamut($value));
			
			# Check if this should be enclosed in a paragraph.
			# Clean tag hashes & block tag hashes are left alone.
			$clean_key = $value;
			$block_key = substr($value, 0, 32);
			
			$is_p = (!isset($this->html_blocks[$block_key]) && 
					 !isset($this->html_cleans[$clean_key]));
			
			if ($is_p) {
				$value = "<p>$value</p>";
			}
			$grafs[$key] = $value;
		}
		
		# Join grafs in one text, then unhash HTML tags. 
		$text = implode("\n\n", $grafs);
		
		# Finish by removing any tag hashes still present in $text.
		$text = $this->unhash($text);
		
		$this->pdf->SetFont('Arial','B',12);
		$this->pdf->Write(5,$text);
	    //Line break
	    $this->pdf->Ln(20);		
		
		return $text;
	}
	
	function transform($text) {
	#
	# Added clear to the new $html_hashes, reordered `hashHTMLBlocks` before 
	# blank line stripping and added extra parameter to `runBlockGamut`.
	#
		# Clear the global hashes. If we don't clear these, you get conflicts
		# from other articles when generating a page which contains more than
		# one article (e.g. an index page that shows the N most recent
		# articles):
		$this->footnotes = array();
		$this->footnotes_ordered = array();
		$this->abbr_desciptions = array();
		$this->abbr_matches = array();
		$this->html_cleans = array();

		//return parent::transform($text);
		parent::transform($text);
		
		return $this->pdf;
	}
}

?>