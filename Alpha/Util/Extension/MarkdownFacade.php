<?php

namespace Alpha\Util\Extension;

use Alpha\Util\Config\ConfigProvider;
use Alpha\View\Widget\Image;
use Alpha\Exception\AlphaException;

/**
 * A facade class for the Markdown library.
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
class MarkdownFacade
{
    /**
     * The markdown-format content that we will render.
     *
     * @var string
     *
     * @since 1.0
     */
    private $content;

    /**
     * The business object that stores the content will be rendered to Markdown.
     *
     * @var \Alpha\Model\ActiveRecord
     *
     * @since 1.0
     */
    private $BO = null;

    /**
     * The auto-generated name of the Markdown HTML cache file for the BO.
     *
     * @var string
     *
     * @since 1.0
     */
    private $filename;

    /**
     * The constructor.
     *
     * @param \Alpha\Model\ActiveRecord $BO
     * @param bool                     $useCache
     *
     * @since 1.0
     */
    public function __construct($BO, $useCache = true)
    {
        $config = ConfigProvider::getInstance();

        $this->BO = $BO;

        if ($this->BO instanceof \Alpha\Model\Article && $this->BO->isLoadedFromFile()) {
            $underscoreTimeStamp = str_replace(array('-', ' ', ':'), '_', $this->BO->getContentFileDate());
            $this->filename = $config->get('app.file.store.dir').'cache/html/'.get_class($this->BO).'_'.$this->BO->get('title').'_'.$underscoreTimeStamp.'.html';
        } else {
            $this->filename = $config->get('app.file.store.dir').'cache/html/'.get_class($this->BO).'_'.$this->BO->getID().'_'.$this->BO->getVersion().'.html';
        }

        if (!$useCache) {
            $this->content = $this->markdown($this->BO->get('content', true));
        } else {
            if ($this->checkCache()) {
                $this->loadCache();
            } else {
                if ($this->BO->get('content', true) == '') {
                    // the content may not be loaded from the DB at this stage due to a previous soft-load
                    $this->BO->reload();
                }

                $this->content = $this->markdown($this->BO->get('content', true));

                $this->cache();
            }
        }

        // Replace all instances of $attachURL in link tags to links to the ViewAttachment controller
        $attachments = array();
        preg_match_all('/href\=\"\$attachURL\/.*\"/', $this->content, $attachments);

        foreach ($attachments[0] as $attachmentURL) {
            $start = mb_strpos($attachmentURL, '/');
            $end = mb_strrpos($attachmentURL, '"');
            $fileName = mb_substr($attachmentURL, $start + 1, $end - ($start + 1));

            if (method_exists($this->BO, 'getAttachmentSecureURL')) {
                $this->content = str_replace($attachmentURL, 'href="'.$this->BO->getAttachmentSecureURL($fileName).'" rel="nofollow"', $this->content);
            }
        }

        // Handle image attachments
        $attachments = array();
        preg_match_all('/\<img\ src\=\"\$attachURL\/.*\.[a-zA-Z]{3}\"[^<]*/', $this->content, $attachments);

        foreach ($attachments[0] as $attachmentURL) {
            preg_match('/\/.*\.[a-zA-Z]{3}/', $attachmentURL, $matches);
            $fileName = $matches[0];

            if ($config->get('cms.images.widget')) {
                // get the details of the source image
                $path = $this->BO->getAttachmentsLocation().$fileName;
                $image_details = getimagesize($path);
                $imgType = $image_details[2];
                if ($imgType == 1) {
                    $type = 'gif';
                } elseif ($imgType == 2) {
                    $type = 'jpg';
                } elseif ($imgType == 3) {
                    $type = 'png';
                }

                $img = new Image($path, $image_details[0], $image_details[1], $type, 0.95, false, (boolean) $config->get('cms.images.widget.secure'));

                $this->content = str_replace($attachmentURL, $img->renderHTMLLink(), $this->content);
            } else {
                // render a normal image link to the ViewAttachment controller
                if (method_exists($this->BO, 'getAttachmentSecureURL')) {
                    $this->content = str_replace($attachmentURL, '<img src="'.$this->BO->getAttachmentSecureURL($fileName).'">', $this->content);
                }
            }
        }
    }

    /**
     * Facade method which will invoke our custom markdown class rather than the standard one.
     *
     * @return string
     *
     * @since 1.0
     */
    public function markdown($text)
    {
        $config = ConfigProvider::getInstance();

        // Initialize the parser and return the result of its transform method.
        static $parser;

        if (!isset($parser)) {
            $parser = new \Alpha\Util\Extension\Markdown();
        }

        /*
         * Replace all instances of $sysURL in the text with the app.url setting from config
         */
        $text = str_replace('$sysURL', $config->get('app.url'), $text);

        // transform text using parser.
        return $parser->transform($text);
    }

    /**
     * Getter for the content.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Saves the HTML generated by Markdown to the cache directory.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 1.0
     */
    private function cache()
    {
        // check to ensure that the article is not transient before caching it
        if (!$this->BO->isTransient() && $this->filename != '') {
            $fp = fopen($this->filename, 'w');
            if (!$fp) {
                throw new AlphaException('Failed to open the cache file for writing, directory permissions my not be set correctly!');
            } else {
                flock($fp, 2); // locks the file for writting
                fwrite($fp, $this->content);
                flock($fp, 3); // unlocks the file
                fclose($fp); //closes the file
            }
        }
    }

    /**
     * Used to check the HTML cache for the BO cache file.
     *
     * @return bool
     *
     * @since 1.0
     */
    public function checkCache()
    {
        return file_exists($this->filename);
    }

    /**
     * Method to load the content of the cache file to the $content attribute of this object.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 1.0
     */
    public function loadCache()
    {
        $fp = fopen($this->filename, 'r');

        if (!$fp) {
            throw new AlphaException('Failed to open the cache file for reading, directory permissions my not be set correctly!');
        } else {
            $this->content = fread($fp, filesize($this->filename));
            fclose($fp); //closes the file
        }
    }
}
