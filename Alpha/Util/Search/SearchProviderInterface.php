<?php

namespace Alpha\Util\Search;

/**
 * A standard interface used for describing search engine implementations.
 *
 * @since 1.2.3
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
 */
interface SearchProviderInterface
{
    /**
     * The main search function, you should provide the user's search query as-is and pass it to the
     * search engine.  An array of business objects will be returned (ordered by the search engine).
     *
     * @param string $query      The search query.
     * @param string $returnType Use this filter to determine that only business objects of a certain class hould be returned (default is to return all classes indexed).
     * @param int    $start      Start point for pagination.
     * @param int    $limit      The maximum amount to return in the list (for pagination).
     *
     * @return array An array of matching business objects.
     *
     * @since 1.2.3
     */
    public function search($query, $returnType = 'all', $start = 0, $limit = 10);

    /**
     * Gets a list of documents related to the business objects matching the object provided.  An array
     * of business objects will be returned (ordered by the search engine).
     *
     * @param \Alpha\Model\ActiveRecord $sourceObject The source object for comparison.
     * @param string                   $returnType   Use this filter to determine that only business objects of a certain class hould be returned (default is to return all classes indexed).
     * @param int                      $start        Start point for pagination.
     * @param int                      $limit        The maximum amount to return in the list (for pagination).
     * @param string                   $distinct     Related items will only be returned that have distinct values in this named field.
     *
     * @return array An array of related business objects.
     *
     * @since 1.2.3
     */
    public function getRelated($sourceObject, $returnType = 'all', $start = 0, $limit = 10, $distinct = '');

    /**
     * Adds/updates the business object provided to the search engine index.
     *
     * @param \Alpha\Model\ActiveRecord $sourceObject The object to add to the search index. The sourceObject should already be stored in the database.
     *
     * @throws \Alpha\Exception\SearchIndexWriteException
     *
     * @since 1.2.3
     */
    public function index($sourceObject);

    /**
     * Deletes the business object provided from the search engine index.
     *
     * @param \Alpha\Model\ActiveRecord $sourceObject The object to delete from the search index.
     *
     * @throws \Alpha\Exception\SearchIndexWriteException
     *
     * @since 1.2.3
     */
    public function delete($sourceObject);

    /**
     * Returns the number of matching objects found in the previous search carried out by this provider.
     *
     * @return int
     *
     * @since 1.2.3
     */
    public function getNumberFound();
}
