<?php

AlphaAutoLoader::loadLib('tcpdf.php');

/**
 * Custom version of the TCPDF library class, allowing for any required overrides.
 *
 * @package alpha::util
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2013, John Collins (founder of Alpha Framework).
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
class AlphaTCPDF extends TCPDF {
	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * Overrides the TCPDF::Image method to decrypt encrypted $file paths from the Image widget, then pass
	 * them to the normal TCPDF::Image along with all of the other (unmodified) parameters.
	 *
	 * @param string $file Name of the file containing the image.
	 * @param float $x Abscissa of the upper-left corner.
	 * @param float $y Ordinate of the upper-left corner.
	 * @param float $w Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
	 * @param float $h Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
	 * @param string $type Image format. Possible values are (case insensitive): JPEG and PNG (whitout GD library) and all images supported by GD: GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM;. If not specified, the type is inferred from the file extension.
	 * @param mixed $link URL or identifier returned by AddLink().
	 * @param string $align Indicates the alignment of the pointer next to image insertion relative to image height. The value can be:<ul><li>T: top-right for LTR or top-left for RTL</li><li>M: middle-right for LTR or middle-left for RTL</li><li>B: bottom-right for LTR or bottom-left for RTL</li><li>N: next line</li></ul>
	 * @param boolean $resize If true resize (reduce) the image to fit $w and $h (requires GD library).
	 * @param int $dpi dot-per-inch resolution used on resize
	 * @param string $palign Allows to center or align the image on the current line. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
	 * @param boolean $ismask true if this image is a mask, false otherwise
	 * @param mixed $imgmask image object returned by this function or false
	 * @param mixed $border Indicates if borders must be drawn around the image. The value can be either a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul>
	 * @since 1.0
	 */
	public function Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaTCPDF');

		global $config;

		self::$logger->debug('Processing image file URL ['.$file.']');

		try {
			if(mb_strpos($file, '/tk/') !== false) {
				$start = mb_strpos($file, '/tk/')+3;
				$end = mb_strlen($file);
				$tk = substr($file, $start+1, $end-($start+1));
				$decoded = FrontController::getDecodeQueryParams($tk);

				parent::Image($decoded['s'], $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border);
			}else {
				// it has no query string, so threat as a regular image URL
				if(AlphaValidator::isURL($file))
					parent::Image($config->get('app.root').'/'.Image::convertImageURLToPath($file), $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border);
				else
					parent::Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border);
			}
		}catch(Exception $e) {
			self::$logger->error('Error processing image file URL ['.$file.'], error ['.$e->getMessage().']');
			throw $e;
		}
	}

	/**
	 * Process opening tags.  Overridden here to fix a bug when the image type can't be determined from the URL.
	 *
	 * @param array $dom html dom array
	 * @param int $key current element id
	 * @param boolean $cell if true add the default cMargin space to each new line (default false).
	 * @access protected
	 */
	protected function openHTMLTagHandler(&$dom, $key, $cell=false) {
		$tag = $dom[$key];
		$parent = $dom[($dom[$key]['parent'])];
		$firstorlast = ($key == 1);
		// check for text direction attribute
		if (isset($tag['attribute']['dir'])) {
			$this->tmprtl = $tag['attribute']['dir'] == 'rtl' ? 'R' : 'L';
		} else {
			$this->tmprtl = false;
		}
		//Opening tag
		switch($tag['value']) {
			case 'table': {
				$cp = 0;
				$cs = 0;
				$dom[$key]['rowspans'] = array();
				if (!$this->empty_string($dom[$key]['thead'])) {
					// set table header
					$this->thead = $dom[$key]['thead'];
				}
				if (isset($tag['attribute']['cellpadding'])) {
					$cp = $this->getHTMLUnitToUnits($tag['attribute']['cellpadding'], 1, 'px');
					$this->oldcMargin = $this->cMargin;
					$this->cMargin = $cp;
				}
				if (isset($tag['attribute']['cellspacing'])) {
					$cs = $this->getHTMLUnitToUnits($tag['attribute']['cellspacing'], 1, 'px');
				}
				$this->checkPageBreak((2 * $cp) + (2 * $cs) + $this->lasth);
				break;
			}
			case 'tr': {
				// array of columns positions
				$dom[$key]['cellpos'] = array();
				break;
			}
			case 'hr': {
				$this->addHTMLVertSpace(1, $cell, '', $firstorlast, $tag['value'], false);
				$this->htmlvspace = 0;
				$wtmp = $this->w - $this->lMargin - $this->rMargin;
				if ((isset($tag['attribute']['width'])) AND ($tag['attribute']['width'] != '')) {
					$hrWidth = $this->getHTMLUnitToUnits($tag['attribute']['width'], $wtmp, 'px');
				} else {
					$hrWidth = $wtmp;
				}
				$x = $this->GetX();
				$y = $this->GetY();
				$prevlinewidth = $this->GetLineWidth();
				$this->Line($x, $y, $x + $hrWidth, $y);
				$this->SetLineWidth($prevlinewidth);
				$this->addHTMLVertSpace(1, $cell, '', !isset($dom[($key + 1)]), $tag['value'], false);
				break;
			}
			case 'a': {
				if (array_key_exists('href', $tag['attribute'])) {
					$this->HREF['url'] = $tag['attribute']['href'];
				}
				$this->HREF['color'] = $this->htmlLinkColorArray;
				$this->HREF['style'] = $this->htmlLinkFontStyle;
				if (array_key_exists('style', $tag['attribute'])) {
					// get style attributes
					preg_match_all('/([^;:\s]*):([^;]*)/', $tag['attribute']['style'], $style_array, PREG_PATTERN_ORDER);
					$astyle = array();
					while (list($id, $name) = each($style_array[1])) {
						$name = strtolower($name);
						$astyle[$name] = trim($style_array[2][$id]);
					}
					if (isset($astyle['color'])) {
						$this->HREF['color'] = $this->convertHTMLColorToDec($astyle['color']);
					}
					if (isset($astyle['text-decoration'])) {
						$this->HREF['style'] = '';
						$decors = explode(' ', strtolower($astyle['text-decoration']));
						foreach ($decors as $dec) {
							$dec = trim($dec);
							if (!$this->empty_string($dec)) {
								if ($dec{0} == 'u') {
									$this->HREF['style'] .= 'U';
								} elseif ($dec{0} == 'l') {
									$this->HREF['style'] .= 'D';
								}
							}
						}
					}
				}
				break;
			}
			case 'img': {
				if (isset($tag['attribute']['src'])) {
					// replace relative path with real server path
					if ($tag['attribute']['src'][0] == '/') {
						$tag['attribute']['src'] = $_SERVER['DOCUMENT_ROOT'].$tag['attribute']['src'];
					}

					$tag['attribute']['src'] = urldecode($tag['attribute']['src']);

					if (!isset($tag['attribute']['width'])) {
						$tag['attribute']['width'] = 0;
					}
					if (!isset($tag['attribute']['height'])) {
						$tag['attribute']['height'] = 0;
					}

					$tag['attribute']['align'] = 'bottom';

					switch($tag['attribute']['align']) {
						case 'top': {
						$align = 'T';
							break;
						}
						case 'middle': {
							$align = 'M';
							break;
						}
						case 'bottom': {
							$align = 'B';
							break;
						}
						default: {
							$align = 'B';
							break;
						}
					}
					$fileinfo = pathinfo($tag['attribute']['src']);
					if (isset($fileinfo['extension']) AND (!$this->empty_string($fileinfo['extension']))) {
						$type = strtolower($fileinfo['extension']);
					}else{
						$type = '';
					}
					$prevy = $this->y;
					$xpos = $this->GetX();
					if (isset($dom[($key - 1)]) AND ($dom[($key - 1)]['value'] == ' ')) {
						if ($this->rtl) {
							$xpos += $this->GetStringWidth(' ');
						} else {
							$xpos -= $this->GetStringWidth(' ');
						}
					}
					$imglink = '';
					if (isset($this->HREF['url']) AND !$this->empty_string($this->HREF['url'])) {
						$imglink = $this->HREF['url'];
						if ($imglink{0} == '#') {
							// convert url to internal link
							$page = intval(substr($imglink, 1));
							$imglink = $this->AddLink();
							$this->SetLink($imglink, 0, $page);
						}
					}
					$border = 0;
					if (isset($tag['attribute']['border']) AND !empty($tag['attribute']['border'])) {
						// currently only support 1 (frame) or a combination of 'LTRB'
						$border = $tag['attribute']['border'];
					}
					if (isset($tag['attribute']['width'])) {
						$iw = $this->getHTMLUnitToUnits($tag['attribute']['width'], 1, 'px', false);
					}
					if (isset($tag['attribute']['height'])) {
						$ih = $this->getHTMLUnitToUnits($tag['attribute']['height'], 1, 'px', false);
					}
					if (($type == 'eps') OR ($type == 'ai')) {
						$this->ImageEps($tag['attribute']['src'], $xpos, $this->GetY(), $iw, $ih, $imglink, true, $align, '', $border);
					} else {
						$this->Image($tag['attribute']['src'], $xpos, $this->GetY(), $iw, $ih, '', $imglink, $align, false, 300, '', false, false, $border);
					}
					switch($align) {
						case 'T': {
							$this->y = $prevy;
							break;
						}
						case 'M': {
							$this->y = (($this->img_rb_y + $prevy - ($tag['fontsize'] / $this->k)) / 2) ;
							break;
						}
						case 'B': {
							$this->y = $this->img_rb_y - ($tag['fontsize'] / $this->k);
							break;
						}
					}
				}
				break;
			}
			case 'dl': {
				++$this->listnum;
				$this->addHTMLVertSpace(0, $cell, '', $firstorlast, $tag['value'], false);
				break;
			}
			case 'dt': {
				$this->addHTMLVertSpace(1, $cell, '', $firstorlast, $tag['value'], false);
				break;
			}
			case 'dd': {
				if ($this->rtl) {
					$this->rMargin += $this->listindent;
				} else {
					$this->lMargin += $this->listindent;
				}
				$this->addHTMLVertSpace(1, $cell, '', $firstorlast, $tag['value'], false);
				break;
			}
			case 'ul':
			case 'ol': {
				$this->addHTMLVertSpace(0, $cell, '', $firstorlast, $tag['value'], false);
				$this->htmlvspace = 0;
				++$this->listnum;
				if ($tag['value'] == 'ol') {
					$this->listordered[$this->listnum] = true;
				} else {
					$this->listordered[$this->listnum] = false;
				}
				if (isset($tag['attribute']['start'])) {
					$this->listcount[$this->listnum] = intval($tag['attribute']['start']) - 1;
				} else {
					$this->listcount[$this->listnum] = 0;
				}
				if ($this->rtl) {
					$this->rMargin += $this->listindent;
				} else {
					$this->lMargin += $this->listindent;
				}
				$this->addHTMLVertSpace(0, $cell, '', $firstorlast, $tag['value'], false);
				$this->htmlvspace = 0;
				break;
			}
			case 'li': {
				$this->addHTMLVertSpace(1, $cell, '', $firstorlast, $tag['value'], false);
				if ($this->listordered[$this->listnum]) {
					// ordered item
					if (isset($parent['attribute']['type']) AND !$this->empty_string($parent['attribute']['type'])) {
						$this->lispacer = $parent['attribute']['type'];
					} elseif (isset($parent['listtype']) AND !$this->empty_string($parent['listtype'])) {
						$this->lispacer = $parent['listtype'];
					} elseif (isset($this->lisymbol) AND !$this->empty_string($this->lisymbol)) {
						$this->lispacer = $this->lisymbol;
					} else {
						$this->lispacer = '#';
					}
					++$this->listcount[$this->listnum];
					if (isset($tag['attribute']['value'])) {
						$this->listcount[$this->listnum] = intval($tag['attribute']['value']);
					}
				} else {
					// unordered item
					if (isset($parent['attribute']['type']) AND !$this->empty_string($parent['attribute']['type'])) {
						$this->lispacer = $parent['attribute']['type'];
					} elseif (isset($parent['listtype']) AND !$this->empty_string($parent['listtype'])) {
						$this->lispacer = $parent['listtype'];
					} elseif (isset($this->lisymbol) AND !$this->empty_string($this->lisymbol)) {
						$this->lispacer = $this->lisymbol;
					} else {
						$this->lispacer = '!';
					}
				}
				break;
			}
			case 'blockquote': {
				if ($this->rtl) {
					$this->rMargin += $this->listindent;
				} else {
					$this->lMargin += $this->listindent;
				}
				$this->addHTMLVertSpace(2, $cell, '', $firstorlast, $tag['value'], false);
				break;
			}
			case 'br': {
				$this->Ln('', $cell);
				break;
			}
			case 'div': {
				$this->addHTMLVertSpace(1, $cell, '', $firstorlast, $tag['value'], false);
				break;
			}
			case 'p': {
				$this->addHTMLVertSpace(2, $cell, '', $firstorlast, $tag['value'], false);
				break;
			}
			case 'pre': {
				$this->addHTMLVertSpace(1, $cell, '', $firstorlast, $tag['value'], false);
				$this->premode = true;
				break;
			}
			case 'sup': {
				$this->SetXY($this->GetX(), $this->GetY() - ((0.7 * $this->FontSizePt) / $this->k));
				break;
			}
			case 'sub': {
				$this->SetXY($this->GetX(), $this->GetY() + ((0.3 * $this->FontSizePt) / $this->k));
				break;
			}
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6': {
				$this->addHTMLVertSpace(1, $cell, ($tag['fontsize'] * 1.5) / $this->k, $firstorlast, $tag['value'], false);
			break;
			}
			case 'tcpdf': {
				// NOT HTML: used to call TCPDF methods
				if (isset($tag['attribute']['method'])) {
					$tcpdf_method = $tag['attribute']['method'];
					if (method_exists($this, $tcpdf_method)) {
						if (isset($tag['attribute']['params']) AND (!empty($tag['attribute']['params']))) {
							eval('$params = array('.$tag['attribute']['params'].');');
							call_user_func_array(array($this, $tcpdf_method), $params);
						} else {
							$this->$tcpdf_method();
						}
						$this->newline = true;
					}
				}
			}
			default: {
				break;
			}
		}
	}
}

?>