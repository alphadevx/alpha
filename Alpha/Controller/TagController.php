<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Http\Response;
use Alpha\View\View;
use Alpha\View\Widget\SmallTextBox;
use Alpha\View\Widget\Button;
use Alpha\Model\Tag;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Type\SmallText;
use Alpha\Controller\Front\FrontController;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\FileNotFoundException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ValidationException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\AlphaException;

/**
 * Controller used to edit Tags related to the ActiveRecord indicated in the supplied
 * GET vars (ActiveRecordType and ActiveRecordID).  If no ActiveRecord Type or ID are
 * indicated, then a screen to manage all tags at a summary level is presented.
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
class TagController extends ActiveRecordController implements ControllerInterface
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
     * constructor to set up the object.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('TagController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        // set up the title and meta details
        $this->setTitle('Editing Tags');
        $this->setDescription('Page to edit tags.');
        $this->setKeywords('edit,tags');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @throws \Alpha\Exception\IllegalArguementException
     * @throws \Alpha\Exception\FileNotFoundException
     *
     * @since 1.0
     */
    public function doGET(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $config = ConfigProvider::getInstance();

        $body = '';
        $fields = array();
        $tagsHTML = '';

        // render the tag manager screen
        if (!isset($params['ActiveRecordType']) && !isset($params['ActiveRecordID'])) {
            $body .= View::displayPageHead($this);

            $message = $this->getStatusMessage();
            if (!empty($message)) {
                $body .= $message;
            }

            $ActiveRecordTypes = ActiveRecord::getRecordClassNames();
            $fieldname = '';

            foreach ($ActiveRecordTypes as $ActiveRecordType) {
                $record = new $ActiveRecordType();

                if ($record->isTagged()) {
                    $tag = new Tag();
                    $count = count($tag->loadAllByAttribute('taggedClass', $ActiveRecordType));
                    $fields['friendlyClassName'] = $record->getFriendlyClassName();
                    $fields['count'] = $count;
                    $fields['fieldname'] = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('clearTaggedClass')) : 'clearTaggedClass');
                    $fields['ActiveRecordType'] = $ActiveRecordType;
                    $fields['adminView'] = true;

                    $tagsHTML .= View::loadTemplateFragment('html', 'tagsadmin.phtml', $fields);
                }
            }

            ActiveRecord::disconnect();
            $fields = array();
            $fields['formAction'] = $request->getURI();
            $fields['fieldname'] = $fieldname;
            $fields['securityFields'] = View::renderSecurityFields();
            $fields['tagsHTML'] = $tagsHTML;

            $body .= View::loadTemplateFragment('html', 'tagslist.phtml', $fields);
        } elseif (isset($params['ActiveRecordType']) && $params['ActiveRecordType'] != 'Alpha\Model\Tag' && isset($params['ActiveRecordID'])) {

            // render screen for managing individual tags on a given active record

            $body .= View::displayPageHead($this);

            $message = $this->getStatusMessage();
            if (!empty($message)) {
                $body .= $message;
            }

            $ActiveRecordType = urldecode($params['ActiveRecordType']);
            $ActiveRecordID = $params['ActiveRecordID'];

            if (class_exists($ActiveRecordType)) {
                $record = new $ActiveRecordType();
            } else {
                throw new IllegalArguementException('No ActiveRecord available to display tags for!');
            }

            try {
                $record->load($ActiveRecordID);

                $tags = $record->getPropObject('tags')->getRelated();

                ActiveRecord::disconnect();

                foreach ($tags as $tag) {
                    $labels = $tag->getDataLabels();

                    $temp = new SmallTextBox($tag->getPropObject('content'), $labels['content'], 'content_'.$tag->getID());
                    $fields['contentSmallTextBox'] = $temp->render(false);
                    $fields['fieldname'] = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('ActiveRecordID')) : 'ActiveRecordID');
                    $fields['tagID'] = $tag->getID();
                    $fields['adminView'] = false;

                    $tagsHTML .= View::loadTemplateFragment('html', 'tagsadmin.phtml', $fields);
                }

                $temp = new SmallTextBox(new SmallText(), 'New tag', 'NewTagValue');
                $fields['newTagValueTextBox'] = $temp->render(false);

                $temp = new Button('submit', 'Save', 'saveBut');
                $fields['saveButton'] = $temp->render();

                if ($params['ActiveRecordType'] = 'Alpha\Model\Article') {
                    $temp = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\ArticleController&ActiveRecordType='.$params['ActiveRecordType'].'&ActiveRecordID='.$params['ActiveRecordID'].'&view=edit')."'", 'Back to record', 'cancelBut');
                } else {
                    $temp = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.$params['ActiveRecordType'].'&ActiveRecordID='.$params['ActiveRecordID'].'&view=edit')."'", 'Back to record', 'cancelBut');
                }
                $fields['cancelButton'] = $temp->render();

                $fields['securityFields'] = View::renderSecurityFields();
                $fields['deleteForm'] = View::renderDeleteForm($request->getURI());
                $fields['formAction'] = $request->getURI();
                $fields['tagsHTML'] = $tagsHTML;

                $body .= View::loadTemplateFragment('html', 'tagsonrecord.phtml', $fields);
            } catch (RecordNotFoundException $e) {
                $msg = 'Unable to load the ActiveRecord of id ['.$params['ActiveRecordID'].'], error was ['.$e->getMessage().']';
                self::$logger->error($msg);
                throw new FileNotFoundException($msg);
            }
        } else {
            return parent::doGET($request);
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
     * @throws \Alpha\Exception\SecurityException
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function doPOST(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doPOST($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
            }

            if (isset($params['clearTaggedClass']) && $params['clearTaggedClass'] != '') {
                try {
                    self::$logger->info('About to start rebuilding the tags for the class ['.$params['clearTaggedClass'].']');
                    $startTime = microtime(true);
                    $record = new $params['clearTaggedClass']();
                    $records = $record->loadAll();
                    self::$logger->info('Loaded all of the active records (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
                    ActiveRecord::begin();
                    $tag = new Tag();
                    $tag->deleteAllByAttribute('taggedClass', $params['clearTaggedClass']);
                    self::$logger->info('Deleted all of the old tags (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
                    $this->regenerateTagsOnRecords($records);
                    self::$logger->info('Saved all of the new tags (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
                    self::$logger->action('Tags recreated on the ['.$params['clearTaggedClass'].'] class');
                    ActiveRecord::commit();
                    $this->setStatusMessage(View::displayUpdateMessage('Tags recreated on the '.$record->getFriendlyClassName().' class.'));
                    self::$logger->info('Tags recreated on the ['.$params['clearTaggedClass'].'] class (time taken ['.round(microtime(true)-$startTime, 5).'] seconds).');
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                    ActiveRecord::rollback();
                }
                ActiveRecord::disconnect();

                return $this->doGET($request);
            } elseif (isset($params['ActiveRecordType']) && isset($params['ActiveRecordID'])) {
                $ActiveRecordType = urldecode($params['ActiveRecordType']);
                $ActiveRecordID = $params['ActiveRecordID'];

                if (class_exists($ActiveRecordType)) {
                    $record = new $ActiveRecordType();
                } else {
                    throw new IllegalArguementException('No ActiveRecord available to display tags for!');
                }

                if (isset($params['saveBut'])) {
                    try {
                        $record->load($ActiveRecordID);

                        $tags = $record->getPropObject('tags')->getRelated();

                        ActiveRecord::begin();

                        foreach ($tags as $tag) {
                            $tag->set('content', Tag::cleanTagContent($params['content_'.$tag->getID()]));
                            $tag->save();
                            self::$logger->action('Saved tag '.$tag->get('content').' on '.$ActiveRecordType.' instance with ID '.$ActiveRecordID);
                        }

                        // handle new tag if posted
                        if (isset($params['NewTagValue']) && trim($params['NewTagValue']) != '') {
                            $newTag = new Tag();
                            $newTag->set('content', Tag::cleanTagContent($params['NewTagValue']));
                            $newTag->set('taggedID', $ActiveRecordID);
                            $newTag->set('taggedClass', $ActiveRecordType);
                            $newTag->save();
                            self::$logger->action('Created a new tag '.$newTag->get('content').' on '.$ActiveRecordType.' instance with ID '.$ActiveRecordID);
                        }

                        ActiveRecord::commit();

                        $this->setStatusMessage(View::displayUpdateMessage('Tags on '.get_class($record).' '.$record->getID().' saved successfully.'));

                        return $this->doGET($request);
                    } catch (ValidationException $e) {
                        /*
                         * The unique key has most-likely been violated because this Record is already tagged with this
                         * value.
                         */
                        ActiveRecord::rollback();

                        $this->setStatusMessage(View::displayErrorMessage('Tags on '.get_class($record).' '.$record->getID().' not saved due to duplicate tag values, please try again.'));

                        return $this->doGET($request);
                    } catch (FailedSaveException $e) {
                        self::$logger->error('Unable to save the tags of id ['.$params['ActiveRecordID'].'], error was ['.$e->getMessage().']');
                        ActiveRecord::rollback();

                        $this->setStatusMessage(View::displayErrorMessage('Tags on '.get_class($record).' '.$record->getID().' not saved, please check the application logs.'));

                        return $this->doGET($request);
                    }
                }
            } else {
                return parent::doPOST($request);
            }
        } catch (SecurityException $e) {
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));

            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());

            $this->setStatusMessage(View::displayErrorMessage('Failed to load the requested item from the database!'));
        }

        self::$logger->debug('<<doPOST');
    }

    /**
     * Handle DELETE requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @throws \Alpha\Exception\SecurityException
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 2.0
     */
    public function doDELETE(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doDELETE($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $this->setName($config->get('app.url').$this->request->getURI());
        $this->setUnitOfWork(array($config->get('app.url').$this->request->getURI(), $config->get('app.url').$this->request->getURI()));

        $request->addParams(array('ActiveRecordType' => 'Alpha\Model\Tag'));

        self::$logger->debug('<<doDELETE');

        return parent::doDELETE($request);
    }

    /**
     * Regenerates the tags on the supplied list of active records.
     *
     * @param array $records
     *
     * @since 1.0
     */
    private function regenerateTagsOnRecords(array $records): void
    {
        foreach ($records as $record) {
            foreach ($record->get('taggedAttributes') as $tagged) {
                $tags = Tag::tokenize($record->get($tagged), get_class($record), $record->getID());
                foreach ($tags as $tag) {
                    try {
                        $tag->save();
                    } catch (ValidationException $e) {
                        /*
                         * The unique key has most-likely been violated because this record is already tagged with this
                         * value, so we can ignore in this case.
                         */
                    }
                }
            }
        }
    }
}
