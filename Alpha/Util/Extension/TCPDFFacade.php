<?php

namespace Alpha\Util\Extension;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Extension\Markdown;
use Alpha\View\Widget\Image;
use Alpha\Exception\AlphaException;

/**
 * A facade class for the TCPDF library which is used to convert some HTML content provided by the
 * Markdown library to a PDF file using FPDF.
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
class TCPDFFacade
{
    /**
     * The HTML-format content that we will render as a PDF.
     *
     * @var string
     *
     * @since 1.0
     */
    private $content;

    /**
     * The PDF object that will be generated from the Markdown HTML content.
     *
     * @var \Alpha\Util\Extension\TCPDF
     *
     * @since 1.0
     */
    private $pdf;

    /**
     * The business object that stores the content will be rendered to Markdown.
     *
     * @var \Alpha\Model\Article
     *
     * @since 1.0
     */
    private $article = null;

    /**
     * The auto-generated name of the PDF cache file for the article.
     *
     * @var string
     *
     * @since 1.0
     */
    private $PDFFilename;

    /**
     * The auto-generated name of the HTML cache file for the article generated by Markdown.
     *
     * @var string
     *
     * @since 1.0
     */
    private $HTMLFilename;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * The constructor.
     *
     * @param \Alpha\Model\ActiveRecord $article the business object that stores the content will be rendered to Markdown
     *
     * @since 1.0
     */
    public function __construct($article)
    {
        self::$logger = new Logger('TCPDFFacade');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        $this->article = $article;

        $reflect = new \ReflectionClass($this->article);
        $classname = $reflect->getShortName();

        $this->PDFFilename = $config->get('app.file.store.dir').'cache/pdf/'.$classname.'_'.$this->article->getID().'_'.$this->article->getVersion().'.pdf';
        $this->HTMLFilename = $config->get('app.file.store.dir').'cache/html/'.$classname.'_'.$this->article->getID().'_'.$this->article->getVersion().'.html';

        // first check the PDF cache
        if ($this->checkPDFCache()) {
            return;
        }

        if ($this->checkHTMLCache()) {
            $this->loadHTMLCache();
        } else {
            $this->content = $this->markdown($this->article->get('content', true));
            $this->HTMLCache();
        }

        // Replace all instances of $attachURL in link tags to links to the ViewAttachment controller
        $attachments = array();
        preg_match_all('/href\=\"\$attachURL\/.*\"/', $this->content, $attachments);

        foreach ($attachments[0] as $attachmentURL) {
            $start = mb_strpos($attachmentURL, '/');
            $end = mb_strrpos($attachmentURL, '"');
            $fileName = mb_substr($attachmentURL, $start + 1, $end - ($start + 1));

            if (method_exists($this->article, 'getAttachmentSecureURL')) {
                $this->content = str_replace($attachmentURL, 'href='.$this->article->getAttachmentSecureURL($fileName), $this->content);
            }
        }

        // Handle image attachments
        $attachments = array();
        preg_match_all('/\<img\ src\=\"\$attachURL\/.*\".*\>/', $this->content, $attachments);

        foreach ($attachments[0] as $attachmentURL) {
            $start = mb_strpos($attachmentURL, '/');
            $end = mb_strrpos($attachmentURL, '" alt');
            $fileName = mb_substr($attachmentURL, $start + 1, $end - ($start + 1));

            if ($config->get('cms.images.widget')) {
                // get the details of the source image
                $path = $this->article->getAttachmentsLocation().'/'.$fileName;
                $image_details = getimagesize($path);
                $imgType = $image_details[2];
                if ($imgType == 1) {
                    $type = 'gif';
                } elseif ($imgType == 2) {
                    $type = 'jpg';
                } else {
                    $type = 'png';
                }

                $img = new Image($path, $image_details[0], $image_details[1], $type, 0.95, false, (boolean) $config->get('cms.images.widget.secure'));
                $this->content = str_replace($attachmentURL, $img->renderHTMLLink(), $this->content);
            } else {
                // render a normal image link to the ViewAttachment controller
                if (method_exists($this->article, 'getAttachmentSecureURL')) {
                    $this->content = str_replace($attachmentURL, '<img src="'.$this->article->getAttachmentSecureURL($fileName).'">', $this->content);
                }
            }
        }

        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor($this->article->get('author'));
        $this->pdf->SetTitle($this->article->get('title'));
        $this->pdf->SetSubject($this->article->get('description'));

        //set margins
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        //set auto page breaks
        $this->pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $this->pdf->setImageScale(2.5);

        // add a page
        $this->pdf->AddPage();

        // add the title
        $title = '<h1>'.$this->article->get('title').'</h1>';
        // add some custom footer info about the article
        $footer = '<br><p>Article URL: <a href="'.$this->article->get('URL').'">'.$this->article->get('URL').'</a><br>Title: '.$this->article->get('title').'<br>Author: '.$this->article->get('author').'</p>';

        // write the title
        self::$logger->debug('Writing the title ['.$title.'] to the PDF');
        $this->pdf->writeHTML(utf8_encode($title), true, false, true, false, '');
        // output the HTML content
        self::$logger->debug('Writing the content ['.$this->content.'] to the PDF');
        $this->pdf->writeHTML(utf8_encode($this->content), true, false, true, false, '');
        // write the article footer
        $this->pdf->writeHTML(utf8_encode($footer), true, false, true, false, '');
        self::$logger->debug('Writing the footer ['.$footer.'] to the PDF');

        // save this PDF to the cache
        $this->pdf->Output($this->PDFFilename, 'F');

        self::$logger->debug('<<__construct()');
    }

     /**
      * Facade method which will invoke our custom markdown class rather than the standard one.
      *
      * @param $text The markdown content to parse
      *
      * @since 1.0
      */
     private function markdown($text)
     {
         $config = ConfigProvider::getInstance();

        /*
         * Initialize the parser and return the result of its transform method.
         *
         */
        static $parser;

         if (!isset($parser)) {
             $parser = new Markdown();
         }

        /*
         * Replace all instances of $sysURL in the text with the app.url setting from config
         *
         */
        $text = str_replace('$sysURL', $config->get('app.url'), $text);

        // transform text using parser.
        return $parser->transform($text);
     }

    /**
     * Fetter for the content.
     *
     * @return string HTML rendered the content
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
    private function HTMLCache()
    {
        // check to ensure that the article is not transient before caching it
        if ($this->article->getID() != '00000000000') {
            $fp = fopen($this->HTMLFilename, 'w');
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
     * Used to check the HTML cache for the article cache file.
     *
     * @return bool true if the file exists, false otherwise
     *
     * @since 1.0
     */
    private function checkHTMLCache()
    {
        return file_exists($this->HTMLFilename);
    }

    /**
     * Method to load the content of the cache file to the $content attribute of this object.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 1.0
     */
    private function loadHTMLCache()
    {
        $fp = fopen($this->HTMLFilename, 'r');

        if (!$fp) {
            throw new AlphaException('Failed to open the cache file for reading, directory permissions my not be set correctly!', 'loadHTMLCache()');
        } else {
            $this->content = fread($fp, filesize($this->HTMLFilename));
            fclose($fp); //closes the file
        }
    }

    /**
     * Used to check the PDF cache for the article cache file.
     *
     * @return bool true if the file exists, false otherwise
     *
     * @since 1.0
     */
    private function checkPDFCache()
    {
        return file_exists($this->PDFFilename);
    }

    /**
     * Returns the raw PDF data stream.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getPDFData()
    {
        // first load the file
        $handle = fopen($this->PDFFilename, 'r');
        $data = fread($handle, filesize($this->PDFFilename));
        fclose($handle);

        return $data;
    }
}
