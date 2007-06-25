<?php

// $Id: markdown_facade.inc 259 2007-03-03 20:47:13Z john $

require_once $sysRoot.'alpha/lib/fpdf/fpdf.php';

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
	
	function alpha_fpdf($orientation='P',$unit='mm',$format='A4')
	{
	    //Call parent constructor
	    $this->FPDF($orientation,$unit,$format);
	    //Initialization
	    $this->B=0;
	    $this->I=0;
	    $this->U=0;
	    $this->HREF='';
	    $this->H1=0;
	}

	function WriteHTML($html)
	{
	    //HTML parser
	    $html=str_replace("\n", ' ', $html);
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

	function OpenTag($tag,$attr)
	{
	    //Opening tag
	    if($tag=='B' or $tag=='I' or $tag=='U' or $tag=='H1')
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
	    	// will use GD to find the dimensions of the image
	    	$image_details = getimagesize($attr["SRC"]);
	    	$this->Image($attr["SRC"], $this->GetX(), $this->GetY());
	    	$this->SetY($this->GetY()+$image_details[1]);
	    } 
	}

	function CloseTag($tag)
	{
	    //Closing tag
	    if($tag=='B' or $tag=='I' or $tag=='U' or $tag=='H1')
	        $this->SetStyle($tag,false);
	    if($tag=='A')
	        $this->HREF='';
	    if($tag=='H1')    	
	    	$this->Ln(20);
	    if($tag=='H2')
	    	$this->Ln(10);	    
	    if($tag=='P')	
	    	$this->Ln(10);	    
	}
	
	function SetStyle($tag,$enable)
	{
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
