<?php

/**
 *
 * Uses the TagObject business oject to store searchable tags in the main
 * application database.
 *
 * @package alpha::util::search
 * @since 1.2.3
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
class SearchProviderTags implements SearchProviderInterface {

	/**
	 * The number of matches found for the current search.
	 *
	 * @var integer
	 * @since 1.2.3
	 */
	private $numberFound = 0;

	/**
     * {@inheritdoc}
     */
	public function search($query, $returnType = 'all', $start = 0, $limit = 10) {

		// explode the user's query into a set of tokenized transient TagObjects
		$queryTags = TagObject::tokenize($query, '', '', false);
		$matchingTags = array();

		// load TagObjects from the DB where content equals the content of one of our transient TagObjects
		foreach($queryTags as $queryTag) {
			$tags = $queryTag->loadAllByAttribute('content', $queryTag->get('content'));
			$matchingTags = array_merge($matchingTags, $tags);
		}

		// the result objects
		$results = array();
		// matching tags with weights
		$matches = array();

		/*
		 * Build an array of BOs for the matching tags from the DB:
		 * array key = BO ID
		 * array value = weight (the amount of tags matching the BO)
		 */
		foreach($matchingTags as $tag) {
			if ($returnType == 'all' || $tag->get('taggedClass') == $returnType) {

				$key = $tag->get('taggedClass').'-'.$tag->get('taggedOID');

				if(isset($matches[$key])) {
					// increment the weight if the same BO is tagged more than once
					$weight = intval($matches[$key]) + 1;
					$matches[$key] = $weight;
				}else{
					$matches[$key] = 1;
				}
			}
		}

		// sort the matches based on tag frequency weight
		arsort($matches);

		$this->numberFound = count($matches);

		// now paginate
		$matches = array_slice($matches, $start, $limit);

		// now load each object
		foreach ($matches as $key => $weight) {
			$parts = explode('-', $key);

			$BO = new $parts[0];
			$BO->load($parts[1]);

			$results[] = $BO;
		}

		return $results;
	}

	/**
     * {@inheritdoc}
     */
	public function getRelated(AlphaDAO $sourceObject, $returnType = 'all', $start = 0, $limit = 10) {
		throw new NotImplementedException('Not implemented yet');
	}

	/**
     * {@inheritdoc}
     */
	public function index(AlphaDAO $sourceObject) {
		$taggedAttributes = $sourceObject->getTaggedAttributes();

		foreach($taggedAttributes as $tagged) {
			$tags = TagObject::tokenize($sourceObject->get($tagged), get_class($sourceObject), $sourceObject->getOID());

			foreach($tags as $tag) {
				try {
					$tag->save();
				}catch(ValidationException $e){
					/*
					 * The unique key has most-likely been violated because this BO is already tagged with this
					 * value, so we can ignore in this case.
					 */
				}
			}
		}
	}

	/**
     * {@inheritdoc}
     */
	public function delete(AlphaDAO $sourceObject) {
		$tags = $sourceObject->getPropObject('tags')->getRelatedObjects();

		foreach ($tags as $tag)
			$tag->delete();
	}

	/**
     * {@inheritdoc}
     */
	public function getNumberFound() {
		return $this->numberFound;
	}
}

?>