<?php

/**
 * 
 * RSS 2.0 class for syndication
 * 
 * @package alpha::util::feeds
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).  
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
class RSS2 extends AlphaFeed {

	/**
	 * The actual root tag used in each feed type
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $rootTag = '<rss version="2.0" />';	
	
	/**
	 * The constructor
	 * 
	 * @param string $BOName The classname of the BO to render a feed for.
	 * @param string $title The title of the feed.
	 * @param string $url The base URL for the feed.
	 * @param string $description The description of the feed.
	 * @param string $pubDate The publish date, only used in Atom feeds.
	 * @param integer $id The feed id, only used in Atom feeds.
	 * @param integer $limit The amount of items to render in the feed.
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function __construct($BOName, $title, $url, $description, $pubDate = null, $id = null, $limit = 10) {
		parent::__construct($BOName, $title, $url, $description, $pubDate, $id, $limit);
		$this->docElement = $this->root;
	}
}

?>