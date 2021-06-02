<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Convertor\ActiveRecord2Excel;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Model\ActiveRecord;

/**
 * Controller for viewing an active record as Excel spreadsheets.
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
class ExcelController extends Controller implements ControllerInterface
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
        self::$logger = new Logger('ExcelController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Public');

        self::$logger->debug('<<__construct');
    }

    /**
     * Loads the Record indicated in the GET request and handles the conversion to Excel.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     *
     * @since 1.0
     */
    public function doGet(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doGet(request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $body = '';

        try {
            if (isset($params['ActiveRecordType'])) {
                $ActiveRecordType = $params['ActiveRecordType'];

                $className = "Alpha\\Model\\$ActiveRecordType";
                if (class_exists($className)) {
                    $this->record = new $className();
                } else {
                    throw new IllegalArguementException('No ActiveRecord available to render!');
                }

                // the name of the file download
                if (isset($params['ActiveRecordID'])) {
                    $fileName = $this->record->getTableName().'-'.$params['ActiveRecordID'];
                } else {
                    $fileName = $this->record->getTableName();
                }

                $response = new Response(200);

                // header info for browser
                $response->setHeader('Content-Type', 'application/vnd.ms-excel');
                $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName.'.xls');
                $response->setHeader('Pragma', 'no-cache');
                $response->setHeader('Expires', '0');

                // handle a single record
                if (isset($params['ActiveRecordID'])) {
                    $this->record->load($params['ActiveRecordID']);
                    ActiveRecord::disconnect();

                    $convertor = new ActiveRecord2Excel($this->record);
                    $body .= $convertor->render();
                } else {
                    // handle all records of this type
                    $records = $this->record->loadAll();
                    ActiveRecord::disconnect();

                    $first = true;

                    foreach ($records as $record) {
                        $convertor = new ActiveRecord2Excel($record);
                        if ($first) {
                            $body .= $convertor->render(true);
                            $first = false;
                        } else {
                            $body .= $convertor->render(false);
                        }
                    }
                }
            } else {
                throw new IllegalArguementException('No ActiveRecordType parameter available for ViewExcel controller!');
            }
        } catch (RecordNotFoundException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException($e->getMessage());
        }

        self::$logger->debug('<<__doGet');
        $response->setBody($body);

        return $response;
    }
}
