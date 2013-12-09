<?php

/**
 *
 * The tag class used in tag clouds and search
 *
 * @package alpha::model
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
class TagObject extends AlphaDAO {
	/**
	 * The name of the class of the object which is tagged
	 *
	 * @var String
	 * @since 1.0
	 */
	protected $taggedClass;

	/**
	 * The OID of the object which is tagged
	 *
	 * @var Integer
	 * @since 1.0
	 */
	protected $taggedOID;

	/**
	 * The content of the tag
	 *
	 * @var String
	 * @since 1.0
	 */
	protected $content;

	/**
	 * An array of data display labels for the class properties
	 *
	 * @var array
	 * @since 1.0
	 */
	protected $dataLabels = array("OID"=>"Tag ID#","taggedClass"=>"Class Name","taggedOID"=>"Tagged Object ID#","content"=>"Tag");

	/**
	 * The name of the database table for the class
	 *
	 * @var string
	 * @since 1.0
	 */
	const TABLE_NAME = 'Tag';

	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * The constructor
	 *
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('TagObject');

		// ensure to call the parent constructor
		parent::__construct();
		$this->taggedClass = new String();
		$this->taggedOID = new Integer();
		$this->content = new String();

		$this->markUnique('taggedClass', 'taggedOID', 'content');
	}

	/**
	 * Returns an array of TagObjects matching the class and OID provided
	 *
	 * @param $taggedClass The class name of the DAO that has been tagged.
	 * @param $taggedOID The Object ID of the DAO that has been tagged.
	 * @return array
	 * @since 1.0
	 * @throws AlphaException
	 * @throws IllegalArguementException
	 */
	public function loadTags($taggedClass, $taggedOID) {

		global $config;

		if($taggedClass == '' || $taggedOID == '')
			throw new IllegalArguementException('The taggedClass or taggedOID provided are empty');

		$provider = AlphaDAOProviderFactory::getInstance($config->get('db.provider.name'), $this);

		try {
			$tags = $provider->loadAllByAttributes(array('taggedOID','taggedClass'), array($taggedOID, $taggedClass));
			return $tags;
		}catch(BONotFoundException $bonf) {
			return array();
		}catch(Exception $e) {
			self::$logger->error($e->getMessage());
			throw new AlphaException($e->getMessage());
		}
	}

	/**
	 * Returns a hash array of the most popular tags based on their occurence in the database,
	 * ordered by alphabet and restricted to the a count matching the $limit supplied.  The
	 * returned has array uses the tag content as a key and the database value as a value.
	 *
	 * @param $limit
	 * @return array
	 * @since 1.0
	 * @throws AlphaException
	 */
	public static function getPopularTagsArray($limit) {
		global $config;

		$provider = AlphaDAOProviderFactory::getInstance($config->get('db.provider.name'), new TagObject());

		$sqlQuery = "SELECT content, count(*) as count FROM ".TagObject::TABLE_NAME." GROUP BY content ORDER BY count DESC LIMIT $limit";

		try{
			$result = $provider->query($sqlQuery);
		}catch(CustomQueryException $e) {
			throw new AlphaException('Failed to query the tags table, error is ['.$e->getMessage().']');
			return array();
		}

		// now build an array of tags to be returned
		$popTags = array();

		foreach($result as $row) {
			$popTags[$row['content']] = $row['count'];
		}

		// sort the array by content key before returning
		ksort($popTags);
		return $popTags;
	}

	/**
	 * Splits the passed content by spaces, filters (removes) stop words from stopwords.ini,
	 * and returns an array of TagObject instances.
	 *
	 * @param $content
	 * @param $taggedClass Optionally provide a BO class name
	 * @param $taggedOID Optionally provide a BO instance OID
	 * @param $applyStopwords Defaults true, set to false if you want to ignore the stopwords.
	 * @return array
	 * @throws AlphaException
	 * @since 1.0
	 */
	public static function tokenize($content, $taggedClass='', $taggedOID='', $applyStopwords=true) {
		if(self::$logger == null)
			self::$logger = new Logger('TagObject');

		global $config;

		// apply stop words
		$lowerWords = preg_split("/[\s,.:-]+/", $content);

		array_walk($lowerWords, 'TagObject::lowercaseArrayElement');

		if($applyStopwords) {
			if(file_exists($config->get('app.root').'config/stopwords-'.$config->get('search.stop.words.size').'.ini')) {
				$stopwords = file($config->get('app.root').'config/stopwords-'.$config->get('search.stop.words.size').'.ini', FILE_IGNORE_NEW_LINES);
			}elseif(file_exists($config->get('app.root').'alpha/stopwords-'.$config->get('search.stop.words.size').'.ini')) {
				$stopwords = file($config->get('app.root').'alpha/stopwords-'.$config->get('search.stop.words.size').'.ini', FILE_IGNORE_NEW_LINES);
			}else{
				throw new AlphaException('Unable to find a stopwords-'.$config->get('search.stop.words.size').'.ini file in the application!');
			}

			array_walk($stopwords, 'TagObject::lowercaseArrayElement');

			$filtered = array_diff($lowerWords, $stopwords);
		}else{
			$filtered = $lowerWords;
		}

		$tagObjects = array();
		$tagContents = array();
		foreach($filtered as $tagContent) {
			// we only want to create word tags
			if(AlphaValidator::isAlpha($tagContent)) {
				// just making sure that we haven't added this one in already
				if(!in_array($tagContent, $tagContents) && !empty($tagContent)) {
					$tag = new TagObject();
					$tag->set('content', trim(mb_strtolower($tagContent)));
					if(!empty($taggedClass))
						$tag->set('taggedClass', $taggedClass);
					if(!empty($taggedOID))
						$tag->set('taggedOID', $taggedOID);

					array_push($tagObjects, $tag);
					array_push($tagContents, $tagContent);
				}
			}
		}

		self::$logger->debug('Tags generated: ['.var_export($tagContents, true).']');
		return $tagObjects;
	}

	/**
	 * Applies trim() and strtolower to the array element passed by reference
	 *
	 * @param $element
	 * @param $key (not required)
	 */
	private static function lowercaseArrayElement(&$element, $key) {
		$element = trim(mb_strtolower($element));
	}

	/**
	 * Cleans tag content by removing white spaces and converting to lowercase.
	 *
	 * @param $content
	 * @return string
	 */
	public static function cleanTagContent($content) {
		return trim(mb_strtolower(str_replace(' ', '', $content)));
	}
}

?>