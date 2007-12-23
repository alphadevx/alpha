<?php

// $Id: markdown_facade.inc 259 2007-03-03 20:47:13Z john $

require_once $sysRoot.'alpha/lib/fpdf/fpdf.php';
require_once $sysRoot.'alpha/view/widgets/image.js.php';

/**
 *
 * A custom version of the FPDF class which includes a HTML parser
 * 
 * @package Alpha Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins 
 * @todo add in more support for other HTML tags!
 * 
 */

class alpha_fpdf extends FPDF {	
	var $B;
	var $I;
	var $U;
	var $HREF;
	var $H1;
	var $CODE;
	var $SPAN;
	
	function alpha_fpdf($orientation='P',$unit='mm',$format='A4')
	{
	    //Call parent constructor
	    $this->FPDF($orientation,$unit,$format);
	    //Initialization	    
	    $this->HREF = '';
	    $this->H1 = 0;
	    $this->CODE = 0;
	    $this->SPAN = 0;
	    $this->OL = 0;
	    $this->OLCount = 1;
	    $this->UL = 0;
	    $this->LI = 0;
	}

	function WriteHTML($html)
	{
	    //HTML parser	   
	    $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
	    foreach($a as $i=>$e)
	    {
	        if($i%2==0)
	        {
	            //Text
	            if($this->HREF)
	                $this->PutLink($this->HREF, $e);
	            elseif($this->H1)
	            	$this->Cell(0, 10, $e, 1, 0, 'C');	            
	            elseif($this->CODE && $this->SPAN)
	            	$this->Write(5, html_entity_decode($e));
	            else
	                $this->Write(5, $e);
	        }
	        else
	        {
	            //Tag
	            if($e{0}=='/')
	                $this->CloseTag(strtoupper(substr($e,1)));
	            else
	            {
	                //Extract attributes
	                $a2 = explode(' ', $e);
	                $tag = strtoupper(array_shift($a2));
	                $attr= array();
	                foreach($a2 as $v)
	                    if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3))
	                        $attr[strtoupper($a3[1])]=$a3[2];
	                $this->OpenTag($tag,$attr);
	            }
	        }
	    }
	}

	/**
	 * Open tag
	 */
	function OpenTag($tag,$attr)
	{
	    // handle em tags as i tags
	    if($tag=='EM')
	    	$tag = 'I';
	    if($tag=='B' or $tag=='STRONG' or $tag=='I' or $tag=='U' or $tag=='H1')
	        $this->SetStyle($tag,true);
	    if($tag=='A')
	        $this->HREF=$attr['HREF'];
	    if($tag=='BR')
	        $this->Ln(5);
	    if($tag=='H1') {
	    	$this->SetFont('Arial','B',15);	    	
	    }
	    if($tag=='H2')
	    	$this->SetFont('Arial','B',12);
	    if($tag=='P')
	    	$this->SetFont('Arial','',10);
	    if($tag=='IMG') {
	    	$imgSrc = $attr["SRC"];
	    	// will use GD to find the dimensions of the image
	    	$image_details = getimagesize($imgSrc);
	    	// if the image is a gif or png, use the custom image widget to convert the image to a jpg
	    	$imgType = $image_details[2];
	    	if($imgType == 1 || $imgType == 3) {
	    		if($imgType == 1)
	    			$type = 'gif';
	    		elseif($imgType == 3)
	    			$type = 'png';
	    			
	    		$imgPath = image::convertImageURLToPath($imgSrc);
	    		
	    		$img = new image($imgPath, $image_details[0], $image_details[1], $type, 0.95, 0, 1);
	    		$imgSrc = $img->filename;
	    	}
	    	// just making sure that the image will be on a new line
	    	$this->Ln(1);
	    	/*
	    	 * check to ensure that there is enough room on the current page to render the image, if
	    	 * not then insert a page break
	    	 */
	    	$remainingY = $this->fh - $this->GetY();	    	
	    	if($remainingY < ($image_details[1]/$this->k))
	    		$this->AddPage('P');	    	
	    	$this->Image($imgSrc, $this->GetX(), $this->GetY());
	    	/*
	    	 * set the internal pointer position for the document to be past the height of the image
	    	 * ($this->k is the scaling factor from the parent class)
	    	 */ 
	    	$this->SetY($this->GetY()+($image_details[1]/$this->k));	    	
	    }
	    if($tag=='CODE') {
	    	$this->SetFont('Arial','',10);
	    	$this->SetTextColor(0,0,255);	    	
	    	$this->CODE = 1;
	    }
	    if($tag=='SPAN') {	    	
	    	$this->SPAN = 1;
	    }
	    // handle lists
	    if($tag=='OL') {
	    	$this->SetFont('Arial','',10);
	    	$this->OL = 1;
	    }
	    if($tag=='UL') {
	    	$this->SetFont('Arial','',10);
	    	$this->UL = 1;
	    }
	    if($tag=='LI' && $this->OL) {
	    	$this->LI = 1;
	    	$this->Write(5, $this->OLCount.'. ');
	    	$this->OLCount++;
	    }
	    if($tag=='LI' && $this->UL) {
	    	$this->LI = 1;
	    	$this->Write(5, 'o ');	    	
	    }
	}

	/**
	 * Closing tag
	 */
	function CloseTag($tag)
	{
	    // handle em tags as i tags
	    if($tag=='EM')
	    	$tag = 'I';
	    if($tag=='B' or $tag=='STRONG' or $tag=='I' or $tag=='U' or $tag=='H1')
	        $this->SetStyle($tag,false);
	    if($tag=='A')
	        $this->HREF='';
	    if($tag=='H1')    	
	    	$this->Ln(5);
	    if($tag=='H2')
	    	$this->Ln(1);	    
	    if($tag=='P')	
	    	$this->Ln(1);
	    if($tag=='CODE') {	    	
	    	$this->CODE=0;
	    	$this->SetTextColor(0,0,0);
	    }	    
	    if($tag=='OL') {	    	
	    	$this->OL = 0;
	    	// reset the OL counter to 1
	    	$this->OLCount = 1;
	    }
	    if($tag=='UL') {	    	
	    	$this->UL = 0;
	    }
	    if($tag=='LI') {
	    	$this->LI = 0;	    	
	    }
	}
	
	function SetStyle($tag,$enable)
	{
		if($tag=="STRONG")
			$tag = "B";
	    //Modify style and select corresponding font
	    $this->$tag+=($enable ? 1 : -1);
	    $style='';
	    foreach(array('B','I','U') as $s)
	        if($this->$s>0)
	            $style.=$s;
	    $this->SetFont('',$style);
	}

	function PutLink($URL,$txt)
	{
	    //Put a hyperlink
	    $this->SetTextColor(0,0,255);
	    $this->SetStyle('U',true);
	    $this->Write(5,$txt,$URL);
	    $this->SetStyle('U',false);
	    $this->SetTextColor(0);
	}	
}

?>
