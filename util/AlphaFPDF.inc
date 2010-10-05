<?php

// $Id$

require_once $config->get('sysRoot').'alpha/lib/fpdf/fpdf.php';
require_once $config->get('sysRoot').'alpha/view/widgets/image.js.php';

/**
 *
 * A custom version of the FPDF class which includes a HTML parser
 * 
 * @package Alpha Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins 
 * @deprecated use TCPDFFacade instead!
 * 
 */
class AlphaFPDF extends FPDF {	
	var $B;
	var $I;
	var $U;
	var $HREF;
	var $H1;
	var $CODE;
	var $SPAN;
	
	//Private properties
	var $tmpFiles = array();
	
	function AlphaFPDF($orientation='P',$unit='mm',$format='A4')
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
	                foreach($a2 as $v) {
	                	// checking for font colours in span elements
	                	if(substr($v, 0, 1) == '#' && $this->SPAN) {
	                		// clean the colour value
	                		$v = str_replace(';','',$v);
	                		$v = str_replace('"','',$v);
	                		$v = str_replace('#','',$v);
			                $RGB = $this->html2rgb($v);
			                $this->SetTextColor($RGB[0],$RGB[1],$RGB[2]);
	                	}
	                    if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3)) {
	                        $attr[strtoupper($a3[1])]=$a3[2];	                    	
	                    }
	                }
	                $this->OpenTag($tag,$attr);
	            }
	        }
	    }
	}

	/**
	 * Open tag
	 */
	function OpenTag($tag,$attr) {
		global $config;
		
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
	    	$this->SetTextColor(0,0,0);    	
	    }
	    if($tag=='H2') {
	    	$this->SetFont('Arial','B',12);
	    	$this->SetTextColor(0,0,0);
	    }
	    if($tag=='P') {
	    	$this->SetFont('Arial','',10);
	    	$this->SetTextColor(0,0,0);
	    }
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
	    		
	    		$img = new image($imgPath, $image_details[0]/$this->k, $image_details[1]/$this->k, $type, 0.95, 0, 1);
	    		$imgSrc = $img->filename;
	    	}
	    	// just making sure that the image will be on a new line
	    	$this->Ln(1);
	    	/*
	    	 * check to ensure that there is enough room on the current page to render the image, if
	    	 * not then insert a page break
	    	 */
	    	$remainingY = $this->h - $this->GetY();	    	
	    	if($remainingY < intval(($image_details[1]/$this->k)/2))
	    		$this->AddPage('P');
	    		
	    	// check config to see if we want to allow transparent PNGs
			if($imgType == 3 && $config->get('sysImagesPerservePNG')) {
				$this->ImagePngWithAlpha($imgSrc, $this->GetX(), $this->GetY(), $image_details[0]/$this->k, $image_details[1]/$this->k);
			}else{
				$this->Image($imgSrc, $this->GetX(), $this->GetY(), intval(($image_details[0]/$this->k)/2), intval(($image_details[1]/$this->k)/2), 'jpg');
			}
			
	    	/*
	    	 * set the internal pointer position for the document to be past the height of the image
	    	 * ($this->k is the scaling factor from the parent class)
	    	 */ 
	    	$this->SetY($this->GetY()+intval(($image_details[1]/$this->k)/2));	    	
	    }
	    if($tag=='CODE') {
	    	$this->SetFont('Arial','',10);	    	
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
	    	$this->Ln(15);
	    if($tag=='H2')
	    	$this->Ln(1);	    
	    if($tag=='P')	
	    	$this->Ln(1);
	    if($tag=='CODE') {	    	
	    	$this->CODE=0;	    	
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
	
	function Footer()
	{
		$this->SetTextColor(0,0,0);
	    //Go to 1.5 cm from bottom
	    $this->SetY(-15);
	    //Select Arial italic 8
	    $this->SetFont('Arial','I',8);
	    //Print centered page number
	    $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
	}
	
	/**
	 * Accepts a hexidecimal colour and returns the RGB equivlent in an array
	 */
	function html2rgb($colour)
	{
	    if ($colour[0] == '#')
	        $colour = substr($colour, 1);
	
	    if (strlen($colour) == 6)
	        list($r, $g, $b) = array($colour[0].$colour[1],
	                                 $colour[2].$colour[3],
	                                 $colour[4].$colour[5]);
	    elseif (strlen($colour) == 3)
	        list($r, $g, $b) = array($colour[0], $colour[1], $colour[2]);
	    else
	        return false;
	
	    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
	
	    return array($r, $g, $b);
	}
	
	function Image($file,$x,$y,$w=0,$h=0,$type='',$link='', $isMask=false, $maskImg=0) {
		global $config;
		
		// PNGs would have been converted to JPGs if sysImagesPerservePNG is false
		if(!$config->get('sysImagesPerservePNG') && $type == 'png')
			$type = 'jpg';
		
		//Put an image on the page
		if(!isset($this->images[$file]))
		{
		    //First use of image, get info
		    if($type=='')
		    {
		        $pos=strrpos($file,'.');
		        if(!$pos)
		            $this->Error('Image file has no extension and no type was specified: '.$file);
		        $type=substr($file,$pos+1);
		    }
		    $type=strtolower($type);
		    $mqr=get_magic_quotes_runtime();
		    set_magic_quotes_runtime(0);
		    if($type=='jpg' || $type=='jpeg')
		        $info=$this->_parsejpg($file);
		    elseif($type=='png'){
		        $info=$this->_parsepng($file);		        
		        if ($info=='alpha' && $config->get('sysImagesPerservePNG')) return $this->ImagePngWithAlpha($file,$x,$y,$w,$h,$link);
			}
			else
			{
		    	// Allow for additional formats
		    	$mtd='_parse'.$type;
		    	if(!method_exists($this,$mtd))
		        	$this->Error('Unsupported image type: '.$type);
		    	$info=$this->$mtd($file);
			}
			set_magic_quotes_runtime($mqr);
		    
			if ($isMask){
		    	$info['cs']="DeviceGray"; // try to force grayscale (instead of indexed)
			}
			$info['i']=count($this->images)+1;
			if ($maskImg>0) $info['masked'] = $maskImg;###
			    $this->images[$file]=$info;
		}
		else
		    $info=$this->images[$file];
		//Automatic width and height calculation if needed
		if($w==0 && $h==0)
		{
		    //Put image at 72 dpi
		    $w=$info['w']/$this->k;
		    $h=$info['h']/$this->k;
		}
		if($w==0)
		    $w=$h*$info['w']/$info['h'];
		if($h==0)
		    $h=$w*$info['h']/$info['w'];
		        
		if ($isMask) $x = $this->fwPt + 10; // embed hidden, ouside the canvas  
		$this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
		if($link)
		    $this->Link($x,$y,$w,$h,$link);
		        
		return $info['i'];		
	}

	// needs GD 2.x extension
	// pixel-wise operation, not very fast
	function ImagePngWithAlpha($file,$x,$y,$w=0,$h=0,$link='')
	{
		global $config;
		
	    $tmp_alpha = tempnam($config->get('sysRoot').'alpha/cache/images/', 'mska');
	    $this->tmpFiles[] = $tmp_alpha;
	    $tmp_plain = tempnam($config->get('sysRoot').'alpha/cache/images/', 'mskp');
	    $this->tmpFiles[] = $tmp_plain;
	    
	    list($wpx, $hpx) = getimagesize($file);
	    $img = imagecreatefrompng($file);
	    $alpha_img = imagecreate( $wpx, $hpx );
	    
	    // generate gray scale pallete
	    for($c=0;$c<256;$c++) ImageColorAllocate($alpha_img, $c, $c, $c);
	    
	    // extract alpha channel
	    $xpx=0;
	    while ($xpx<$wpx){
	        $ypx = 0;
	        while ($ypx<$hpx){
	            $color_index = imagecolorat($img, $xpx, $ypx);
	            $col = imagecolorsforindex($img, $color_index);
	            imagesetpixel($alpha_img, $xpx, $ypx, $this->_gamma( (127-$col['alpha'])*255/127)  );
	        ++$ypx;
	        }
	        ++$xpx;
	    }
	
	    imagepng($alpha_img, $tmp_alpha);
	    imagedestroy($alpha_img);
	    
	    // extract image without alpha channel
	    $plain_img = imagecreatetruecolor ( $wpx, $hpx );
	    imagecopy ($plain_img, $img, 0, 0, 0, 0, $wpx, $hpx );
	    imagepng($plain_img, $tmp_plain);
	    imagedestroy($plain_img);
	    
	    //first embed mask image (w, h, x, will be ignored)
	    $maskImg = $this->Image($tmp_alpha, 0,0,0,0, 'PNG', '', true);
	    
	    //embed image, masked with previously embedded mask
	    $this->Image($tmp_plain,$x,$y,$w,$h,'PNG',$link, false, $maskImg);
	}

	function Close()
	{
	    parent::Close();
	    // clean up tmp files
	    foreach($this->tmpFiles as $tmp) @unlink($tmp);
	}

	/*******************************************************************************
	*                                                                              *
	*                               Private methods                                *
	*                                                                              *
	*******************************************************************************/
	function _putimages()
	{
	    $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	    reset($this->images);
	    while(list($file,$info)=each($this->images))
	    {//$this->images[$file] = array();
	        $this->_newobj();
	        $this->images[$file]['n']=$this->n;
	        $this->_out('<</Type /XObject');
	        $this->_out('/Subtype /Image');
	        $this->_out('/Width '.$info['w']);
	        $this->_out('/Height '.$info['h']);
	        
	        if (isset($info["masked"])) $this->_out('/SMask '.($this->n-1).' 0 R'); ###
	        
	        if($info['cs']=='Indexed')
	            $this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
	        else
	        {
	            $this->_out('/ColorSpace /'.$info['cs']);
	            if($info['cs']=='DeviceCMYK')
	                $this->_out('/Decode [1 0 1 0 1 0 1 0]');
	        }
	        $this->_out('/BitsPerComponent '.$info['bpc']);
	        if(isset($info['f']))
	            $this->_out('/Filter /'.$info['f']);
	        if(isset($info['parms']))
	            $this->_out($info['parms']);
	        if(isset($info['trns']) && is_array($info['trns']))
	        {
	            $trns='';
	            for($i=0;$i<count($info['trns']);$i++)
	                $trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
	            $this->_out('/Mask ['.$trns.']');
	        }
	        $this->_out('/Length '.strlen($info['data']).'>>');
	        $this->_putstream($info['data']);
	        unset($this->images[$file]['data']);	        
	        $this->_out('endobj');
	        //Palette
	        if($info['cs']=='Indexed')
	        {
	            $this->_newobj();
	            $pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
	            $this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
	            $this->_putstream($pal);
	            $this->_out('endobj');
	        }
	    }
	}
	
	// GD seems to use a different gamma, this method is used to correct it again
	function _gamma($v){
	    return pow ($v/255, 2.2) * 255;
	}
	
	// this method overwriing the original version is only needed to make the Image method support PNGs with alpha channels.
	// if you only use the ImagePngWithAlpha method for such PNGs, you can remove it from this script.
	/*function _parsepng($file)
	{
	    //Extract info from a PNG file
	    $f=fopen($file,'rb');
	    if(!$f)
	        $this->Error('Can\'t open image file: '.$file);
	    //Check signature
	    if(fread($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
	        $this->Error('Not a PNG file: '.$file);
	    //Read header chunk
	    fread($f,4);
	    if(fread($f,4)!='IHDR')
	        $this->Error('Incorrect PNG file: '.$file);
	    $w=$this->_freadint($f);
	    $h=$this->_freadint($f);
	    $bpc=ord(fread($f,1));
	    if($bpc>8)
	        $this->Error('16-bit depth not supported: '.$file);
	    $ct=ord(fread($f,1));
	    if($ct==0)
	        $colspace='DeviceGray';
	    elseif($ct==2)
	        $colspace='DeviceRGB';
	    elseif($ct==3)
	        $colspace='Indexed';
	    else {
	        fclose($f);      // the only changes are
	        return 'alpha';  // made in those 2 lines
	    }
	    if(ord(fread($f,1))!=0)
	        $this->Error('Unknown compression method: '.$file);
	    if(ord(fread($f,1))!=0)
	        $this->Error('Unknown filter method: '.$file);
	    if(ord(fread($f,1))!=0)
	        $this->Error('Interlacing not supported: '.$file);
	    fread($f,4);
	    $parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
	    //Scan chunks looking for palette, transparency and image data
	    $pal='';
	    $trns='';
	    $data='';
	    do
	    {
	        $n=$this->_freadint($f);
	        $type=fread($f,4);
	        if($type=='PLTE')
	        {
	            //Read palette
	            $pal=fread($f,$n);
	            fread($f,4);
	        }
	        elseif($type=='tRNS')
	        {
	            //Read transparency info
	            $t=fread($f,$n);
	            if($ct==0)
	                $trns=array(ord(substr($t,1,1)));
	            elseif($ct==2)
	                $trns=array(ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1)));
	            else
	            {
	                $pos=strpos($t,chr(0));
	                if($pos!==false)
	                    $trns=array($pos);
	            }
	            fread($f,4);
	        }
	        elseif($type=='IDAT')
	        {
	            //Read image data block
	            $data.=fread($f,$n);
	            fread($f,4);
	        }
	        elseif($type=='IEND')
	            break;
	        else
	            fread($f,$n+4);
	    }
	    while($n);
	    if($colspace=='Indexed' && empty($pal))
	        $this->Error('Missing palette in '.$file);
	    fclose($f);
	    return array('w'=>$w,'h'=>$h,'cs'=>$colspace,'bpc'=>$bpc,'f'=>'FlateDecode','parms'=>$parms,'pal'=>$pal,'trns'=>$trns,'data'=>$data);
	}*/
}

?>
