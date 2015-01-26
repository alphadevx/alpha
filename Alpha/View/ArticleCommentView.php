<?php

namespace Alpha\View;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Extension\MarkdownFacade;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Model\Person;
use Alpha\View\Widget\TextBox;
use Alpha\View\Widget\Button;
use Alpha\Controller\Front\FrontController;

/**
 *
 * The rendering class for the ArticleComment class
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
class ArticleCommentView extends View
{
    /**
     * Method to generate the markdown HTML render of the ArticleComment content
     *
     * @since 1.0
     */
    public function markdownView()
    {
        $config = ConfigProvider::getInstance();

        $markdown = new MarkdownFacade($this->BO);
        $author = new Person();
        $id = $this->BO->getCreatorID();
        $author->load($id->getValue());

        echo '<blockquote class="usercomment">';

        $createTS = $this->BO->getCreateTS();
        $updateTS = $this->BO->getUpdateTS();

        echo '<p>Posted by '.($author->get('URL') == ''? $author->get('displayname') : '<a href="'.$author->get('URL').'" target="new window">'.$author->get('displayname').'</a>').' at '.$createTS->getValue().'.';
        echo '&nbsp;'.$author->get('displayname').' has posted ['.$author->getCommentCount().'] comments on articles since joining.';
        echo '</p>';
        if ($config->get('cms.comments.allowed') && isset($_SESSION['currentUser']) && $_SESSION['currentUser']->getID() == $author->getID())
            $this->editView();
        else
            echo $markdown->getContent();

        if ($createTS->getValue() != $updateTS->getValue()) {
            $updator = new Person();
            $id = $this->BO->getCreatorID();
            $updator->load($id->getValue());
            echo '<p>Updated by '.($updator->get('URL') == ''? $updator->get('displayname') : '<a href="'.$updator->get('URL').'" target="new window">'.$updator->get('displayname').'</a>').' at '.$updateTS->getValue().'.</p>';
        }
        echo '</blockquote>';
    }

    /**
     * Renders the custom create view
     *
     * @param array $fields hash array of HTML fields to pass to the template
     * @since 1.0
     */
    public function createView($fields=array())
    {
        echo '<h2>Post a new comment:</h2>';

        echo '<table cols="2" class="create_view">';
        echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" accept-charset="UTF-8">';

        $textBox = new TextBox($this->BO->getPropObject('content'), $this->BO->getDataLabel('content'), 'content', '', 10);
        echo $textBox->render();

        echo '<input type="hidden" name="articleOID" value="'.$this->BO->get('articleOID').'"/>';
        echo '<tr><td colspan="2">';

        $button = new Button('submit', 'Post Comment', 'createBut');
        echo $button->render();

        echo '</td></tr>';

        echo View::renderSecurityFields();

        echo '</form></table>';
        echo '<p class="warning">Please note that any comment you post may be moderated for spam or offensive material.</p>';
    }

    /**
     * Custom edit view
     *
     * @param array $fields Hash array of HTML fields to pass to the template.
     * @since 1.0
     */
    public function editView($fields=array())
    {
        $config = ConfigProvider::getInstance();

        echo '<table cols="2" class="edit_view" style="width:100%; margin:0px">';
        echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" accept-charset="UTF-8">';

        $textBox = new TextBox($this->BO->getPropObject('content'), $this->BO->getDataLabel('content'), 'content', '', 5, $this->BO->getID());
        echo $textBox->render();

        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('version_num')) : 'version_num');
        echo '<input type="hidden" name="'.$fieldname.'" value="'.$this->BO->getVersion().'"/>';
        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('article_comment_id')) : 'article_comment_id');
        echo '<input type="hidden" name="'.$fieldname.'" value="'.$this->BO->getID().'"/>';

        // render special buttons for admins only
        if ($_SESSION['currentUser']->inGroup('Admin') && strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
            echo '<tr><td colspan="2">';

            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('saveBut')) : 'saveBut');
            $temp = new Button('submit', 'Save', $fieldname);
            echo $temp->render();
            echo '&nbsp;&nbsp;';
            $js = "$('#dialogDiv').text('Are you sure you wish to delete this item?');
                $('#dialogDiv').dialog({
                buttons: {
                    'OK': function(event, ui) {
                        $('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('deleteOID')) : 'deleteOID')."\"]').attr('value', '".$this->BO->getOID()."');
                        $('#deleteForm').submit();
                    },
                    'Cancel': function(event, ui) {
                        $(this).dialog('close');
                    }
                }
            })
            $('#dialogDiv').dialog('open');
            return false;";
            $temp = new Button($js, "Delete", "deleteBut");
            echo $temp->render();
            echo '&nbsp;&nbsp;';
            $temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",'Back to List','cancelBut');
            echo $temp->render();
            echo '</td></tr>';

            echo View::renderSecurityFields();

            echo '</form></table>';
        } else {
            echo '</table>';

            echo '<div align="center">';
            $temp = new Button('submit', 'Update Your Comment', 'saveBut'.$this->BO->getID());
            echo $temp->render();
            echo '</div>';

            echo View::renderSecurityFields();

            echo '</form>';
        }
    }
}

?>