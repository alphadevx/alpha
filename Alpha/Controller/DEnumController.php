<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\View\View;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\FailedSaveException;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;

/**
 *
 * Controller used to edit DEnums and associated DEnumItems
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
class DEnumController extends EditController implements ControllerInterface
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * constructor to set up the object
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('DEnumController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        // set up the title and meta details
        $this->setTitle('Editing a DEnum');
        $this->setDescription('Page to edit a DEnum.');
        $this->setKeywords('edit,DEnum');

        $this->BO = new DEnum();

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param array $params
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function doGET($params)
    {
        self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

        $config = ConfigProvider::getInstance();

        echo View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message))
            echo $message;

        // ensure that a OID is provided
        if (isset($params['oid'])) {
            $BOoid = $params['oid'];
        } else {
            throw new IllegalArguementException('Could not load the DEnum object as an oid was not supplied!');
            return;
        }

        try {
            $this->BO->load($BOoid);

            ActiveRecord::disconnect();

            $this->BOName = 'DEnum';

            $this->BOView = View::getInstance($this->BO);

            echo View::renderDeleteForm();

            echo $this->BOView->editView();
        } catch (RecordNotFoundException $e) {
            self::$logger->error('Unable to load the DEnum of id ['.$params['oid'].'], error was ['.$e->getMessage().']');
        }

        echo View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
    }

    /**
     * Handle POST requests
     *
     * @param array $params
     * @throws Alpha\Exception\SecurityException
     * @since 1.0
     */
    public function doPOST($params)
    {
        self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
                self::$logger->debug('<<doPOST');
            }

            // ensure that a OID is provided
            if (isset($params['oid'])) {
                $BOoid = $params['oid'];
            } else {
                throw new IllegalArguementException('Could not load the DEnum object as an oid was not supplied!');
            }

            if (isset($params['saveBut'])) {
                try {
                    $this->BO->load($BOoid);
                    // update the object from post data
                    $this->BO->populateFromPost();

                    ActiveRecord::begin();

                    $this->BO->save();

                    self::$logger->action('DEnum '.$this->BO->getOID().' saved');

                    // now save the DEnumItems
                    $tmp = new DEnumItem();
                    $denumItems = $tmp->loadItems($this->BO->getID());

                    foreach ($denumItems as $item) {
                        $item->set('value', $params['value_'.$item->getID()]);
                        $item->save();

                        self::$logger->action('DEnumItem '.$item->getOID().' saved');
                    }

                    // handle new DEnumItem if posted
                    if(isset($params['new_value']) && trim($params['new_value']) != '') {
                        $newItem = new DEnumItem();
                        $newItem->set('value', $params['new_value']);
                        $newItem->set('DEnumID', $this->BO->getID());
                        $newItem->save();

                        self::$logger->action('DEnumItem '.$newItem->getOID().' created');
                    }

                    ActiveRecord::commit();

                    $this->setStatusMessage(View::displayUpdateMessage(get_class($this->BO).' '.$this->BO->getID().' saved successfully.'));

                    return $this->doGET($params);
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

        echo View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message))
            echo $message;

        echo View::displayPageFoot($this);
        self::$logger->debug('<<doPOST');
    }

    /**
     * Use this callback to inject in the admin menu template fragment
     *
     * @since 1.2
     */
    public function after_displayPageHead_callback()
    {
        $menu = View::loadTemplateFragment('html', 'adminmenu.phtml', array());

        return $menu;
    }
}

?>