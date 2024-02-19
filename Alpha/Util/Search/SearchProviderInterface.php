<?php

namespace Alpha\Util\Search;

/**
 * A standard interface used for describing search engine implementations.
 *
 * @since 1.2.3
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
     * @param string $returnType Use this filter to determine that only business objects of a certain class should be returned (default is to return all classes indexed).
     * @param int    $start      Start point for pagination.
     * @param int    $limit      The maximum amount to return in the list (for pagination).
     * @param int    $createdBy  Optionally provide the creator ID to restrict search to Tags created by that user.
     *
     * @since 1.2.3
     */
    public function search(string $query, string $returnType = 'all', int $start = 0, int $limit = 10, int $createdBy = 0): array;

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
     * @since 1.2.3
     */
    public function getRelated(\Alpha\Model\ActiveRecord $sourceObject, string $returnType = 'all', int $start = 0, int $limit = 10, string $distinct = ''): array;

    /**
     * Adds/updates the business object provided to the search engine index.
     *
     * @param \Alpha\Model\ActiveRecord $sourceObject The object to add to the search index. The sourceObject should already be stored in the database.
     *
     * @throws \Alpha\Exception\SearchIndexWriteException
     *
     * @since 1.2.3
     */
    public function index(\Alpha\Model\ActiveRecord $sourceObject): void;

    /**
     * Deletes the business object provided from the search engine index.
     *
     * @param \Alpha\Model\ActiveRecord $sourceObject The object to delete from the search index.
     *
     * @throws \Alpha\Exception\SearchIndexWriteException
     *
     * @since 1.2.3
     */
    public function delete(\Alpha\Model\ActiveRecord $sourceObject): void;

    /**
     * Returns the number of matching objects found in the previous search carried out by this provider.
     *
     * @since 1.2.3
     */
    public function getNumberFound(): int;
}
