<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\View\View;
use Alpha\View\Widget\StringBox;
use Alpha\View\Widget\Button;
use Alpha\Model\Tag;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Type\String;
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
 * Controller used to edit Tags related to the ActiveRecord indicated in the supplied
 * GET vars (ActiveRecordType and ActiveRecordOID).
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
     * @param Alpha\Util\Http\Request $request
     * @return Alpha\Util\Http\Response
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\FileNotFoundException
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $config = ConfigProvider::getInstance();

        $body = View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message))
            $body .= $message;

        // ensure that an ActiveRecordType is provided
        if (isset($params['ActiveRecordType']))
            $ActiveRecordType = $params['ActiveRecordType'];
        else
            throw new IllegalArguementException('Could not load the tag objects as an ActiveRecordType was not supplied!');

        // ensure that a OID is provided
        if (isset($params['ActiveRecordOID']))
            $ActiveRecordOID = $params['ActiveRecordOID'];
        else
            throw new IllegalArguementException('Could not load the tag objects as an ActiveRecordOID was not supplied!');

        $className = "Alpha\\Model\\$ActiveRecordType";
        if (class_exists($className))
            $this->BO = new $className();
        else
            throw new IllegalArguementException('No ActiveRecord available to display tags for!');

        try {
            $this->BO->load($ActiveRecordOID);

            $tags = $this->BO->getPropObject('tags')->getRelatedObjects();

            ActiveRecord::disconnect();

            $body .= '<form action="'.$request->getURI().'" method="POST" accept-charset="UTF-8">';
            $body .= '<h3>The following tags were found:</h3>';

            foreach ($tags as $tag) {
                $labels = $tag->getDataLabels();

                $temp = new StringBox($tag->getPropObject('content'), $labels['content'], 'content_'.$tag->getID(), '');
                $body .= $temp->render(false);

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
                $body .= $button->render();
            }

            $body .= '<h3>Add a new tag:</h3>';

            $temp = new StringBox(new String(), 'New tag', 'NewTagValue', '');
            $body .= $temp->render(false);

            $temp = new Button('submit', 'Save', 'saveBut');
            $body .= $temp->render();
            $body .= '&nbsp;&nbsp;';
            $temp = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.$params['ActiveRecordType'].'&oid='.$params['ActiveRecordOID'])."'", 'Back to Object', 'cancelBut');
            $body .= $temp->render();

            $body .= View::renderSecurityFields();

            $body .= '</form>';

            $body .= View::renderDeleteForm($request->getURI());

        } catch (RecordNotFoundException $e) {
            $msg = 'Unable to load the ActiveRecord of id ['.$params['ActiveRecordOID'].'], error was ['.$e->getMessage().']';
            self::$logger->error($msg);
            throw new FileNotFoundException($msg);
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle POST requests
     *
     * @param Alpha\Util\Http\Request $request
     * @return Alpha\Util\Http\Response
     * @throws Alpha\Exception\SecurityException
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doPOST($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields())
                throw new SecurityException('This page cannot accept post data from remote servers!');

            // ensure that a bo is provided
            if (isset($params['ActiveRecordType']))
                $ActiveRecordType = $params['ActiveRecordType'];
            else
                throw new IllegalArguementException('Could not load the tag objects as an ActiveRecordType was not supplied!');

            // ensure that a OID is provided
            if (isset($params['ActiveRecordOID']))
                $ActiveRecordOID = $params['ActiveRecordOID'];
            else
                throw new IllegalArguementException('Could not load the tag objects as an ActiveRecordOID was not supplied!');

            $className = "Alpha\\Model\\$ActiveRecordType";
            if (class_exists($className))
                $this->BO = new $className();
            else
                throw new IllegalArguementException('No ActiveRecord available to display tags for!');

            if (isset($params['saveBut'])) {
                try {
                    $this->BO->load($ActiveRecordOID);

                    $tags = $this->BO->getPropObject('tags')->getRelatedObjects();

                    ActiveRecord::begin();

                    foreach ($tags as $tag) {
                        $tag->set('content', Tag::cleanTagContent($params['content_'.$tag->getID()]));
                        $tag->save();
                        self::$logger->action('Saved tag '.$tag->get('content').' on '.$ActiveRecordType.' instance with OID '.$ActiveRecordOID);
                    }

                    // handle new tag if posted
                    if (isset($params['NewTagValue']) && trim($params['NewTagValue']) != '') {
                        $newTag = new Tag();
                        $newTag->set('content', Tag::cleanTagContent($params['NewTagValue']));
                        $newTag->set('taggedOID', $ActiveRecordOID);
                        $newTag->set('taggedClass', $ActiveRecordType);
                        $newTag->save();
                        self::$logger->action('Created a new tag '.$newTag->get('content').' on '.$ActiveRecordType.' instance with OID '.$ActiveRecordOID);
                    }

                    ActiveRecord::commit();

                    $this->setStatusMessage(View::displayUpdateMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' saved successfully.'));

                    return $this->doGET($params);
                } catch (ValidationException $e) {
                    /*
                     * The unique key has most-likely been violated because this BO is already tagged with this
                     * value.
                     */
                    ActiveRecord::rollback();

                    $this->setStatusMessage(View::displayErrorMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' not saved due to duplicate tag values, please try again.'));

                    return $this->doGET($params);
                } catch (FailedSaveException $e) {
                    self::$logger->error('Unable to save the tags of id ['.$params['ActiveRecordOID'].'], error was ['.$e->getMessage().']');
                    ActiveRecord::rollback();

                    $this->setStatusMessage(View::displayErrorMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' not saved, please check the application logs.'));

                    return $this->doGET($params);
                }

                ActiveRecord::disconnect();
            }

            if (!empty($params['deleteOID'])) {
                try {
                    $this->BO = new $ActiveRecordType;
                    $this->BO->load($ActiveRecordOID);

                    $tag = new Tag();
                    $tag->load($params['deleteOID']);
                    $content = $tag->get('content');

                    ActiveRecord::begin();

                    $tag->delete();

                    self::$logger->action('Deleted tag '.$content.' on '.$ActiveRecordType.' instance with OID '.$ActiveRecordOID);

                    ActiveRecord::commit();

                    $this->setStatusMessage(View::displayUpdateMessage('Tag <em>'.$content.'</em> on '.get_class($this->BO).' '.$this->BO->getID().' deleted successfully.'));

                    return $this->doGET($params);
                } catch (AlphaException $e) {
                    self::$logger->error('Unable to delete the tag of id ['.$params['deleteOID'].'], error was ['.$e->getMessage().']');
                    ActiveRecord::rollback();

                    $this->setStatusMessage(View::displayErrorMessage('Tag <em>'.$content.'</em> on '.get_class($this->BO).' '.$this->BO->getID().' not deleted, please check the application logs.'));

                    return $this->doGET($params);
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