<?php

namespace Alpha\Controller;

use Alpha\Exception\ResourceNotFoundException;
use Alpha\View\Widget\RecordSelector;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Model\Type\Relation;

/**
 * Controller for viewing a RecordSelector widget.
 *
 * @since 1.0
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
class RecordSelectorController extends Controller implements ControllerInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('RecordSelectorController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Public');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handles get requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     */
    public function doGet(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doGet(request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $relation = new Relation();

        $body = '';

        try {
            $relationType = $params['relationType'];
            $ActiveRecordID = $params['ActiveRecordID'];
            $field = $params['field'];
        } catch (\Exception $e) {
            self::$logger->error('Required param missing for RecordSelectorController controller['.$e->getMessage().']');
            throw new ResourceNotFoundException('File not found');
        }

        if ($relationType == 'MANY-TO-MANY') {
            try {
                $relatedClassLeft = urldecode($params['relatedClassLeft']);
                $relatedClassLeftDisplayField = $params['relatedClassLeftDisplayField'];
                $relatedClassRight = urldecode($params['relatedClassRight']);
                $relatedClassRightDisplayField = $params['relatedClassRightDisplayField'];
                $accessingClassName = urldecode($params['accessingClassName']);
                $lookupIDs = $params['lookupIDs'];
            } catch (\Exception $e) {
                self::$logger->error('Required param missing for RecordSelectorController controller['.$e->getMessage().']');
                throw new ResourceNotFoundException('File not found');
            }

            $relation->setRelatedClass($relatedClassLeft, 'left');
            $relation->setRelatedClassDisplayField($relatedClassLeftDisplayField, 'left');
            $relation->setRelatedClass($relatedClassRight, 'right');
            $relation->setRelatedClassDisplayField($relatedClassRightDisplayField, 'right');
            $relation->setRelationType($relationType);
            $relation->setValue($ActiveRecordID);

            $recSelector = new RecordSelector($relation, '', $field, $accessingClassName);
            $body .= $recSelector->renderSelector($field, explode(',', $lookupIDs));
        } else {
            try {
                $relatedClass = urldecode($params['relatedClass']);
                $relatedClassField = $params['relatedClassField'];
                $relatedClassDisplayField = $params['relatedClassDisplayField'];
            } catch (\Exception $e) {
                self::$logger->error('Required param missing for RecordSelectorController controller['.$e->getMessage().']');
                throw new ResourceNotFoundException('File not found');
            }

            $relation->setRelatedClass($relatedClass);
            $relation->setRelatedClassField($relatedClassField);
            $relation->setRelatedClassDisplayField($relatedClassDisplayField);
            $relation->setRelationType($relationType);
            $relation->setValue($ActiveRecordID);

            $recSelector = new RecordSelector($relation);
            $body .= $recSelector->renderSelector($field);
        }

        self::$logger->debug('<<__doGet');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }
}
