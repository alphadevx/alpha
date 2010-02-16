<?php

require_once $config->get('sysRoot').'alpha/lib/tcpdf/tcpdf.php';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';

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
		 * Overrides the TCPDF::Image method to decrypt encrypted $file paths from the Image widget, then pass
		 * them to the normal TCPDF::Image along with all of the other (unmodified) parameters.
		 * 
		 * (non-PHPdoc)
		 * @see alpha/lib/tcpdf/TCPDF#Image()
		 */
		public function Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0) {
			global $config;
			
			$parts = parse_url($file);
			$query = $parts['query'];
			$exploded = explode('=', $query);
			$tk = $exploded[1];
			$decoded = FrontController::getDecodeQueryParams($tk);
			
			parent::Image($config->get('sysRoot').'/'.$decoded['s'], $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0);
		}	
}

?>