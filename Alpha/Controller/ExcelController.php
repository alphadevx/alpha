<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Convertor\ActiveRecord2Excel;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Model\ActiveRecord;

/**
 *
 * Controller for viewing an active record as Excel spreadsheets
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
class ExcelController extends Controller implements ControllerInterface
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
        self::$logger = new Logger('ExcelController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Public');

        self::$logger->debug('<<__construct');
    }

    /**
     * Loads the BO indicated in the GET request and handles the conversion to Excel
     *
     * @param array $params
     * @throws Alpha\Exception\ResourceNotFoundException
     * @since 1.0
     */
    public function doGet($params)
    {
        self::$logger->debug('>>doGet(params=['.var_export($params, true).'])');

        try {
            if (isset($params['bo'])) {
                ActiveRecord::loadClassDef($params['bo']);
                $BO = new $params['bo'];

                // the name of the file download
                if (isset($params['oid']))
                    $fileName = $BO->getTableName().'-'.$params['oid'];
                else
                    $fileName = $BO->getTableName();

                //header info for browser
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename='.$fileName.'.xls');
                header('Pragma: no-cache');
                header('Expires: 0');

                // handle a single BO
                if (isset($params['oid'])) {
                    $BO->load($params['oid']);
                    ActiveRecord::disconnect();

                    $convertor = new ActiveRecord2Excel($BO);
                    $convertor->render();
                } else {
                    // handle all BOs of this type
                    $BOs = $BO->loadAll();
                    ActiveRecord::disconnect();

                    $first = true;

                    foreach ($BOs as $BO) {
                        $convertor = new ActiveRecord2Excel($BO);
                        if ($first) {
                            $convertor->render(true);
                            $first = false;
                        } else {
                            $convertor->render(false);
                        }
                    }
                }
            } else {
                throw new IllegalArguementException('No BO parameter available for ViewExcel controller!');
            }
        } catch (RecordNotFoundException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException($e->getMessage());
        }

        self::$logger->debug('<<__doGet');
    }

    /**
     * Handle POST requests
     *
     * @param array $params
     * @since 1.0
     */
    public function doPOST($params) {
        self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

        self::$logger->debug('<<doPOST');
    }
}

?>