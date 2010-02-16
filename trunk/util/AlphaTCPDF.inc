<?php

require_once $config->get('sysRoot').'alpha/lib/tcpdf/tcpdf.php';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';
require_once $config->get('sysRoot').'alpha/util/helpers/Validator.inc';
require_once $config->get('sysRoot').'alpha/view/widgets/Image.inc';

/**
 * Custom version of the TCPDF library class, allowing for any required overrides.
 * 
 * @package alpha::util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$
 */
class AlphaTCPDF extends TCPDF {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * Overrides the TCPDF::Image method to decrypt encrypted $file paths from the Image widget, then pass
	 * them to the normal TCPDF::Image along with all of the other (unmodified) parameters.
	 * 
	 * (non-PHPdoc)
	 * @see alpha/lib/tcpdf/TCPDF#Image()
	 */
	public function Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaTCPDF');
		
		global $config;
			
		self::$logger->debug('Processing image file URL ['.$file.']');
		
		try {
			$parts = parse_url($file);
			if(isset($parts['query'])) {
				$query = $parts['query'];
				$exploded = explode('=', $query);
				$tk = $exploded[1];
				$decoded = FrontController::getDecodeQueryParams($tk);
				
				parent::Image($config->get('sysRoot').'/'.$decoded['s'], $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border);
			}else {
				// it has no query string, so threat as a regular image URL
				if(Validator::isURL($file))
					parent::Image($config->get('sysRoot').'/'.Image::convertImageURLToPath($file), $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border);
				else
					parent::Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border);
			}
		}catch(Exception $e) {
			self::$logger->error('Error processing image file URL ['.$file.'], parts parsed as ['.print_r($parts, true).']');
			throw $e;
		}
	}	
}

?>