<?php

namespace Alpha\Controller;

use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\IllegalArguementException;
use Alpha\View\Widget\RecordSelector;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Model\Type\Relation;

/**
 * Controller for viewing a RecordSelector widget.
 *
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class RecordSelectorController extends Controller implements ControllerInterface
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor
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
     * Handles get requests
     *
     * @param Alpha\Util\Http\Request $request
     * @return Alpha\Util\Http\Response
     * @since 1.0
     * @throws Alpha\Exception\ResourceNotFoundException
     */
    public function doGet($request)
    {
        self::$logger->debug('>>doGet(request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $relationObject = new Relation();

        $body = '';

        try {
            $relationType = $params['relationType'];
            $ActiveRecordOID = $params['ActiveRecordOID'];
        } catch (\Exception $e) {
            self::$logger->error('Required param missing for ViewRecordSelector controller['.$e->getMessage().']');
            throw new ResourceNotFoundException('File not found');
        }

        $field = $params['field'];

        if ($relationType == 'MANY-TO-MANY') {
            try {
                $relatedClassLeft = $params['relatedClassLeft'];
                $relatedClassLeftDisplayField = $params['relatedClassLeftDisplayField'];
                $relatedClassRight = $params['relatedClassRight'];
                $relatedClassRightDisplayField = $params['relatedClassRightDisplayField'];
                $accessingClassName = $params['accessingClassName'];
                $lookupOIDs = $params['lookupOIDs'];
            } catch (\Exception $e) {
                self::$logger->error('Required param missing for ViewRecordSelector controller['.$e->getMessage().']');
                throw new ResourceNotFoundException('File not found');
            }

            $relationObject->setRelatedClass($relatedClassLeft, 'left');
            $relationObject->setRelatedClassDisplayField($relatedClassLeftDisplayField, 'left');
            $relationObject->setRelatedClass($relatedClassRight, 'right');
            $relationObject->setRelatedClassDisplayField($relatedClassRightDisplayField, 'right');
            $relationObject->setRelationType($relationType);
            $relationObject->setValue($ActiveRecordOID);

            $recSelector = new RecordSelector($relationObject, '', $field, $accessingClassName);
            $body .= $recSelector->renderSelector($field, explode(',', $lookupOIDs));
        } else {
            try {
                $relatedClass = $params['relatedClass'];
                $relatedClassField = $params['relatedClassField'];
                $relatedClassDisplayField = $params['relatedClassDisplayField'];
            } catch (\Exception $e) {
                self::$logger->error('Required param missing for ViewRecordSelector controller['.$e->getMessage().']');
                throw new ResourceNotFoundException('File not found');
            }

            $relationObject->setRelatedClass($relatedClass);
            $relationObject->setRelatedClassField($relatedClassField);
            $relationObject->setRelatedClassDisplayField($relatedClassDisplayField);
            $relationObject->setRelationType($relationType);
            $relationObject->setValue($ActiveRecordOID);

            $recSelector = new RecordSelector($relationObject);
            $body .= $recSelector->renderSelector($field);
        }

        self::$logger->debug('<<__doGet');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }
}

?>