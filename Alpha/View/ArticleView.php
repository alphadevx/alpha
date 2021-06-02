<?php

namespace Alpha\View;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Extension\MarkdownFacade;
use Alpha\Util\Security\SecurityUtils;
use Alpha\View\Widget\Button;
use Alpha\Controller\Front\FrontController;

/**
 * The rendering class for the Article class.
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
class ArticleView extends View
{
    /**
     * Method to generate the markdown HTML render of the article content.
     *
     * @param array $fields Hash array of HTML fields to pass to the template.
     *
     * @since 1.0
     */
    public function markdownView($fields = array()): string
    {
        $markdown = new MarkdownFacade($this->record);

        $fields['markdownContent'] = $markdown->getContent();

        return $this->loadTemplate($this->record, 'markdown', $fields);
    }

    /**
     * Adds a note to the create article screen.
     *
     * @since 1.0
     */
    protected function afterCreateView(): string
    {
        return '<p><strong>Please note</strong> that you will only be able to attach files to the article once it has been created.</p><br>';
    }

    /**
     * Renders the list view (adds the dateAdded field for the list template).
     *
     * @param array $fields hash array of HTML fields to pass to the template
     *
     * @since 1.0
     */
    public function listView($fields = array()): string
    {
        $fields['dateAdded'] = $this->record->getCreateTS()->getDate();
        $fields['editButtonURL'] = FrontController::generateSecureURL('act=Alpha\Controller\ArticleController&ActiveRecordType='.get_class($this->record).'&ActiveRecordID='.$this->record->getID().'&view=edit');

        return parent::listView($fields);
    }

    /**
     * Renders the admin view for the Article, with create button pointed to the ArticleController.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @since 2.0.1
     */
    public function adminView($fields = array()): string
    {
        $fields['createButtonURL'] = FrontController::generateSecureURL('act=Alpha\Controller\ArticleController&ActiveRecordType='.get_class($this->record).'&view=create');

        return parent::adminView($fields);
    }

    /**
     * Renders the detail view for the Article, with edit button pointed to the ArticleController.
     *
     * @param array $fields Hash array of fields to pass to the template
     *
     * @since 2.0.1
     */
    public function detailedView($fields = array()): string
    {
        $fields['editButtonURL'] = FrontController::generateSecureURL('act=Alpha\Controller\ArticleController&ActiveRecordType='.get_class($this->record).'&ActiveRecordID='.$this->record->getID().'&view=edit');

        return parent::detailedView($fields);
    }

    /**
     * Renders a form to enable article editing with attachments options.
     *
     * @param array $fields hash array of HTML fields to pass to the template
     *
     * @since 1.0
     */
    public function editView($fields = array()): string
    {
        if (method_exists($this, 'before_editView_callback')) {
            $this->{'before_editView_callback'}();
        }

        $config = ConfigProvider::getInstance();

        // the form action
        if (isset($fields['URI'])) {
            $fields['formAction'] = $fields['URI'];
        }

        // the form ID
        $fields['formID'] = stripslashes(get_class($this->record)).'_'.$this->record->getID();

        // buffer form fields to $formFields
        $fields['formFields'] = $this->renderAllFields('edit');

        // buffer HTML output for Create and Cancel buttons
        $button = new Button('submit', 'Save', 'saveBut');
        $fields['saveButton'] = $button->render();

        $formFieldId = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('ActiveRecordID')) : 'ActiveRecordID');

        $js = View::loadTemplateFragment('html', 'bootstrapconfirmokay.phtml', array('prompt' => 'Are you sure you wish to delete this item?', 'formFieldId' => $formFieldId, 'formFieldValue' => $this->record->getID(), 'formId' => 'deleteForm'));

        $button = new Button($js, 'Delete', 'deleteBut');
        $fields['deleteButton'] = $button->render();

        $button = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.get_class($this->record).'&start=0&limit='.$config->get('app.list.page.amount'))."'", 'Back to List', 'cancelBut');
        $fields['cancelButton'] = $button->render();

        $tags = array();

        if (is_object($this->record->getPropObject('tags'))) {
            $tags = $this->record->getPropObject('tags')->getRelated();
        }

        if (count($tags) > 0) {
            $button = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\TagController&ActiveRecordType='.get_class($this->record).'&ActiveRecordID='.$this->record->getID())."'", 'Edit Tags', 'tagsBut');
            $fields['tagsButton'] = $button->render();
        }

        // buffer security fields to $formSecurityFields variable
        $fields['formSecurityFields'] = $this->renderSecurityFields();

        // ID will need to be posted for optimistic lock checking
        $fields['version_num'] = $this->record->getVersionNumber();

        // file attachments section
        $fields['fileAttachments'] = $this->renderFileUploadSection();

        if (method_exists($this, 'after_editView_callback')) {
            $this->{'after_editView_callback'}();
        }

        return $this->loadTemplate($this->record, 'edit', $fields);
    }

    /**
     * Renders the HTML for the file upload section.
     *
     * @since 1.0
     */
    protected function renderFileUploadSection(): string
    {
        $config = ConfigProvider::getInstance();

        $html = '<div class="form-group">';
        $html .= '  <h3>File Attachments:</h3>';

        if (is_dir($this->record->getAttachmentsLocation())) {
            $handle = opendir($this->record->getAttachmentsLocation());

            $fileCount = 0;

            $html .= '<table class="table table-bordered">';

            // loop over the attachments directory
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    ++$fileCount;

                    $html .= '<tr>';

                    $html .= '<td>'.$file.' <em>('.number_format(filesize($this->record->getAttachmentsLocation().'/'.$file)/1024).' KB)</em></td>';

                    $js = "if(window.jQuery) {
                            BootstrapDialog.show({
                                title: 'Confirmation',
                                message: 'Are you sure you wish to delete this item?',
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
                                            $('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('deletefile')) : 'deletefile')."\"]').attr('value', '".$file."');
                                            $('[id=\"".stripslashes(get_class($this->record)).'_'.$this->record->getID()."\"]').submit();
                                            dialogItself.close();
                                        }
                                    }
                                ]
                            });
                        }";
                    $button = new Button($js, 'Delete', 'delete'.$fileCount.'But');
                    $html .= '<td>'.$button->render().'</td>';
                    $html .= '</tr>';
                }
            }

            $html .= '</table>';
        } else {
            // we will take this opportunity to create the attachments folder is it does
            // not already exist.
            $this->record->createAttachmentsFolder();
        }

        $html .= '<span class="btn btn-default btn-file">';
        $html .= '<input name="userfile" type="file" value="Browse..."/>';
        $html .= '</span>';

        $temp = new Button('submit', 'Upload', 'uploadBut');
        $html .= $temp->render();

        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('deletefile')) : 'deletefile');
        $html .= '<input type="hidden" name="'.$fieldname.'" id="'.$fieldname.'" value=""/>';

        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('articleID')) : 'articleID');
        $html .= '<input type="hidden" name="'.$fieldname.'" id="'.$fieldname.'" value="'.$this->record->getID().'"/>';

        $html .= '</div>';

        return $html;
    }
}
