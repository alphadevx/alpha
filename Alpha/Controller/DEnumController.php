<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Response;
use Alpha\View\View;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;

/**
 * Controller used to edit DEnums and associated DEnumItems.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class DEnumController extends ActiveRecordController implements ControllerInterface
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
     * DEnum to work on
     *
     * @var \Alpha\Model\Type\DEnum
     *
     * @since 3.0.0
     */
    protected $record;

    /**
     * constructor to set up the object.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('DEnumController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        $this->record = new DEnum();

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @return \Alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $body = '';

        // load one DEnum
        if (isset($params['denumID'])) {
            $RecordOid = $params['denumID'];

            // set up the title and meta details
            $this->setTitle('Editing a DEnum');
            $this->setDescription('Page to edit a DEnum.');
            $this->setKeywords('edit,DEnum');

            $body .= View::displayPageHead($this);

            $message = $this->getStatusMessage();
            if (!empty($message)) {
                $body .= $message;
            }

            try {
                $this->record->load($RecordOid);

                ActiveRecord::disconnect();

                $view = View::getInstance($this->record);

                $body .= View::renderDeleteForm($request->getURI());

                $body .= $view->editView(array('URI' => $request->getURI()));
            } catch (RecordNotFoundException $e) {
                self::$logger->error('Unable to load the DEnum of id ['.$params['denumID'].'], error was ['.$e->getMessage().']');
            }
        } else { // load all DEnums
            // set up the title and meta details
            $this->setTitle('Listing all DEnums');
            $this->setDescription('Page to list all DEnums.');
            $this->setKeywords('list,all,DEnums');

            $body .= View::displayPageHead($this);

            // make sure that the DEnum tables exist
            if (!$this->record->checkTableExists()) {
                $body .= View::displayErrorMessage('Warning! The DEnum tables do not exist, attempting to create them now...');
                $body .= $this->createDEnumTables();
            }

            // get all of the records and invoke the list view on each one

            // set the start point for the list pagination
            if (isset($params['start']) ? $this->start = $params['start'] : $this->start = 1);

            $objects = $this->record->loadAll($this->start);

            ActiveRecord::disconnect();

            $this->recordCount = $this->record->getCount();

            $body .= View::renderDeleteForm($request->getURI());

            foreach ($objects as $object) {
                $temp = View::getInstance($object);
                $body .= $temp->listView(array('URI' => $request->getURI()));
            }
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle POST requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @return \Alpha\Util\Http\Response
     *
     * @throws \Alpha\Exception\SecurityException
     *
     * @since 1.0
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doPOST($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                self::$logger->debug('<<doPOST');
                throw new SecurityException('This page cannot accept post data from remote servers!');
            }

            // ensure that a ID is provided
            if (isset($params['denumID'])) {
                $RecordOid = $params['denumID'];
            } else {
                throw new IllegalArguementException('Could not load the DEnum object as an denumID was not supplied!');
            }

            if (isset($params['saveBut'])) {
                try {
                    $this->record->load($RecordOid);
                    // update the object from post data
                    $this->record->populateFromArray($params);

                    ActiveRecord::begin();

                    $this->record->save();

                    self::$logger->action('DEnum '.$this->record->getID().' saved');

                    // now save the DEnumItems
                    $tmp = new DEnumItem();
                    $denumItems = $tmp->loadItems($this->record->getID());

                    foreach ($denumItems as $item) {
                        $item->set('value', $params['value_'.$item->getID()]);
                        $item->save();

                        self::$logger->action('DEnumItem '.$item->getID().' saved');
                    }

                    // handle new DEnumItem if posted
                    if (isset($params['new_value']) && trim($params['new_value']) != '') {
                        $newItem = new DEnumItem();
                        $newItem->set('value', $params['new_value']);
                        $newItem->set('DEnumID', $this->record->getID());
                        $newItem->save();

                        self::$logger->action('DEnumItem '.$newItem->getID().' created');
                    }

                    ActiveRecord::commit();

                    $this->setStatusMessage(View::displayUpdateMessage(get_class($this->record).' '.$this->record->getID().' saved successfully.'));

                    return $this->doGET($request);
                } catch (FailedSaveException $e) {
                    self::$logger->error('Unable to save the DEnum of id ['.$params['oid'].'], error was ['.$e->getMessage().']');
                    ActiveRecord::rollback();
                }

                ActiveRecord::disconnect();
            }
        } catch (SecurityException $e) {
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
            self::$logger->error($e->getMessage());
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());
            $this->setStatusMessage(View::displayErrorMessage('Failed to load the requested item from the database!'));
        }

        $body = View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message)) {
            $body .= $message;
        }

        $body .= View::displayPageFoot($this);
        self::$logger->debug('<<doPOST');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Method to create the DEnum tables if they don't exist.
     *
     * @since 1.0
     *
     * @return string
     */
    private function createDEnumTables()
    {
        $tmpDEnum = new DEnum();

        $body = '<p>Attempting to build table '.DEnum::TABLE_NAME.' for class DEnum : </p>';

        try {
            $tmpDEnum->makeTable();
            $body .= View::displayUpdateMessage('Successfully re-created the database table '.DEnum::TABLE_NAME);
            self::$logger->action('Re-created the table '.DEnum::TABLE_NAME);
        } catch (AlphaException $e) {
            $body .= View::displayErrorMessage('Failed re-created the database table '.DEnum::TABLE_NAME.', check the log');
            self::$logger->error($e->getMessage());
        }

        $tmpDEnumItem = new DEnumItem();

        $body .= '<p>Attempting to build table '.DEnumItem::TABLE_NAME.' for class DEnumItem : </p>';

        try {
            $tmpDEnumItem->makeTable();
            $body .= View::displayUpdateMessage('Successfully re-created the database table '.DEnumItem::TABLE_NAME);
            self::$logger->action('Re-created the table '.DEnumItem::TABLE_NAME);
        } catch (AlphaException $e) {
            $body .= View::displayErrorMessage('Failed re-created the database table '.DEnumItem::TABLE_NAME.', check the log');
            self::$logger->error($e->getMessage());
        }

        return $body;
    }
}
