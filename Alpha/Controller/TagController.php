<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\View\View;
use Alpha\View\Widget\StringBox;
use Alpha\View\Widget\Button;
use Alpha\Model\Tag;
use Alpha\Model\ActiveRecord;
use Alpha\Controller\Front\FrontController;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\FileNotFoundException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ValidationException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\AlphaException;

/**
 *
 * Controller used to edit Tags related to the BO indicated in the supplied
 * GET vars (bo and oid).
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
class TagController extends EditController implements ControllerInterface
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
        self::$logger = new Logger('TagController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        // set up the title and meta details
        $this->setTitle('Editing Tags');
        $this->setDescription('Page to edit tags.');
        $this->setKeywords('edit,tags');

        $this->BO = new Tag();

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param array $params
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\FileNotFoundException
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

        // ensure that a bo is provided
        if (isset($params['bo']))
            $BOName = $params['bo'];
        else
            throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');

        // ensure that a OID is provided
        if (isset($params['oid']))
            $BOoid = $params['oid'];
        else
            throw new IllegalArguementException('Could not load the tag objects as an oid was not supplied!');

        try {
            ActiveRecord::loadClassDef($BOName);
            $this->BO = new $BOName;
            $this->BO->load($BOoid);

            $tags = $this->BO->getPropObject('tags')->getRelatedObjects();

            ActiveRecord::disconnect();

            echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" accept-charset="UTF-8">';
            echo '<h3>The following tags were found:</h3>';

            foreach ($tags as $tag) {
                $labels = $tag->getDataLabels();

                $temp = new StringBox($tag->getPropObject('content'), $labels['content'], 'content_'.$tag->getID(), '');
                echo $temp->render(false);

                $js = "if(window.jQuery) {
                    BootstrapDialog.show({
                        title: 'Confirmation',
                        message: 'Are you sure you wish to delete this tag?',
                        buttons: [
                            {
                                icon: 'glyphicon glyphicon-remove',
                                label: 'Cancel',
                                cssClass: 'btn btn-default btn-xs',
                                action: function(dialogItself){
                                    dialogItself.close();
                                }
                            },
                            {
                                icon: 'glyphicon glyphicon-ok',
                                label: 'Okay',
                                cssClass: 'btn btn-default btn-xs',
                                action: function(dialogItself) {
                                    $('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('deleteOID')) : 'deleteOID')."\"]').attr('value', '".$tag->getID()."');
                                    $('#deleteForm').submit();
                                    dialogItself.close();
                                }
                            }
                        ]
                    });
                }";
                $button = new Button($js, "Delete", "delete".$tag->getID()."But");
                echo $button->render();
            }

            echo '<h3>Add a new tag:</h3>';

            $temp = new StringBox(new String(), 'New tag', 'new_value', '');
            echo $temp->render(false);

            $temp = new Button('submit', 'Save', 'saveBut');
            echo $temp->render();
            echo '&nbsp;&nbsp;';
            $temp = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.$params['bo'].'&oid='.$params['oid'])."'", 'Back to Object', 'cancelBut');
            echo $temp->render();

            echo View::renderSecurityFields();

            echo '</form>';

            echo View::renderDeleteForm();

        } catch (RecordNotFoundException $e) {
            $msg = 'Unable to load the BO of id ['.$params['oid'].'], error was ['.$e->getMessage().']';
            self::$logger->error($msg);
            throw new FileNotFoundException($msg);
        }

        echo View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
    }

    /**
     * Handle POST requests
     *
     * @param array $params
     * @throws Alpha\Exception\SecurityException
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function doPOST($params)
    {
        self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

        try {
            // check the hidden security fields before accepting the form POST data
            if(!$this->checkSecurityFields())
                throw new SecurityException('This page cannot accept post data from remote servers!');

            // ensure that a bo is provided
            if (isset($params['bo']))
                $BOName = $params['bo'];
            else
                throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');

            // ensure that a OID is provided
            if (isset($params['oid']))
                $BOoid = $params['oid'];
            else
                throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');

            if (isset($params['saveBut'])) {
                try {
                    ActiveRecord::loadClassDef($BOName);
                    $this->BO = new $BOName;
                    $this->BO->load($BOoid);

                    $tags = $this->BO->getPropObject('tags')->getRelatedObjects();

                    ActiveRecord::begin();

                    foreach ($tags as $tag) {
                        $tag->set('content', Tag::cleanTagContent($params['content_'.$tag->getID()]));
                        $tag->save();
                        self::$logger->action('Saved tag '.$tag->get('content').' on '.$BOName.' instance with OID '.$BOoid);
                    }

                    // handle new tag if posted
                    if (isset($params['new_value']) && trim($params['new_value']) != '') {
                        $newTag = new Tag();
                        $newTag->set('content', Tag::cleanTagContent($params['new_value']));
                        $newTag->set('taggedOID', $BOoid);
                        $newTag->set('taggedClass', $BOName);
                        $newTag->save();
                        self::$logger->action('Created a new tag '.$newTag->get('content').' on '.$BOName.' instance with OID '.$BOoid);
                    }

                    ActiveRecord::commit();

                    $this->setStatusMessage(View::displayUpdateMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' saved successfully.'));

                    $this->doGET($params);
                } catch (ValidationException $e) {
                    /*
                     * The unique key has most-likely been violated because this BO is already tagged with this
                     * value.
                     */
                    ActiveRecord::rollback();

                    $this->setStatusMessage(View::displayErrorMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' not saved due to duplicate tag values, please try again.'));

                    $this->doGET($params);
                } catch (FailedSaveException $e) {
                    self::$logger->error('Unable to save the tags of id ['.$params['oid'].'], error was ['.$e->getMessage().']');
                    ActiveRecord::rollback();

                    $this->setStatusMessage(View::displayErrorMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' not saved, please check the application logs.'));

                    $this->doGET($params);
                }

                ActiveRecord::disconnect();
            }

            if (!empty($params['deleteOID'])) {
                try {
                    ActiveRecord::loadClassDef($BOName);
                    $this->BO = new $BOName;
                    $this->BO->load($BOoid);

                    $tag = new Tag();
                    $tag->load($params['deleteOID']);
                    $content = $tag->get('content');

                    ActiveRecord::begin();

                    $tag->delete();

                    self::$logger->action('Deleted tag '.$content.' on '.$BOName.' instance with OID '.$BOoid);

                    ActiveRecord::commit();

                    $this->setStatusMessage(View::displayUpdateMessage('Tag <em>'.$content.'</em> on '.get_class($this->BO).' '.$this->BO->getID().' deleted successfully.'));

                    $this->doGET($params);
                } catch (AlphaException $e) {
                    self::$logger->error('Unable to delete the tag of id ['.$params['deleteOID'].'], error was ['.$e->getMessage().']');
                    ActiveRecord::rollback();

                    $this->setStatusMessage(View::displayErrorMessage('Tag <em>'.$content.'</em> on '.get_class($this->BO).' '.$this->BO->getID().' not deleted, please check the application logs.'));

                    $this->doGET($params);
                }

                ActiveRecord::disconnect();
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
}

?>