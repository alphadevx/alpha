<?php

namespace Alpha\Model;

use Alpha\Model\Type\SmallText;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\Text;
use Alpha\Model\Type\LargeText;
use Alpha\Model\Type\Boolean;
use Alpha\Model\Type\Relation;
use Alpha\Util\Config\Configprovider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Exception\ValidationException;
use Alpha\Exception\FileNotFoundException;
use Alpha\Exception\AlphaException;
use Alpha\Controller\Front\FrontController;

/**
 * An article class for the CMS.
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
class Article extends ActiveRecord
{
    /**
     * The article title.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    protected $title;

    /**
     * The description of the article.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    protected $description;

    /**
     * Optional custom body onload Javascript.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    protected $bodyOnload;

    /**
     * Any custom HTML header content (e.g. Javascript) for the article.
     *
     * @var \Alpha\Model\Type\Text
     *
     * @since 1.0
     */
    protected $headerContent;

    /**
     * The article content.
     *
     * @var \Alpha\Model\Type\LargeText
     *
     * @since 1.0
     */
    protected $content;

    /**
     * The author of the article.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 1.0
     */
    protected $author;

    /**
     * A boolean to control whether the artcile is publically accessible or not.
     *
     * @var \Alpha\Model\Type\Boolean
     *
     * @since 1.0
     */
    protected $published;

    /**
     * A Relation containing all of the comments on this article.
     *
     * @var \Alpha\Model\Type\Relation
     *
     * @since 1.0
     */
    protected $comments;

    /**
     * A Relation containing all of the votes on this article.
     *
     * @var \Alpha\Model\Type\Relation
     *
     * @since 1.0
     */
    protected $votes;

    /**
     * A Relation containing all of the tags on this article.
     *
     * @var \Alpha\Model\Type\Relation
     *
     * @since 1.0
     */
    protected $tags;

    /**
     * An array of all of the attributes on this Record which are tagged.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $taggedAttributes = array('title', 'description', 'content');

    /**
     * Path to a .text file where the content of this article is stored (optional).
     *
     * @var string
     *
     * @since 1.0
     */
    private $filePath;

    /**
     * An array of data display labels for the class properties.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $dataLabels = array('ID' => 'Article ID#', 'title' => 'Title', 'description' => 'Description', 'bodyOnload' => 'Body onload Javascript', 'content' => 'Content', 'headerContent' => 'HTML Header Content', 'author' => 'Author', 'created_ts' => 'Date Added', 'updated_ts' => 'Date of last Update', 'published' => 'Published', 'URL' => 'URL', 'printURL' => 'Printer version URL', 'comments' => 'Comments', 'votes' => 'Votes', 'tags' => 'Tags');

    /**
     * The name of the database table for the class.
     *
     * @var string
     *
     * @since 1.0
     */
    public const TABLE_NAME = 'Article';

    /**
     * The URL for this article (transient).
     *
     * @var string
     *
     * @since 1.0
     */
    protected $URL;

    /**
     * The print URL for this article (transient).
     *
     * @var string
     *
     * @since 1.0
     */
    protected $printURL;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * The constructor which sets up some housekeeping attributes.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('Article');

        $config = ConfigProvider::getInstance();
        $separator = $config->get('cms.url.title.separator');

        // ensure to call the parent constructor
        parent::__construct();

        $this->title = new SmallText();
        $this->title->setHelper('Please provide a title for the article. Note that the '.$separator.' character is not allowed!');
        $this->title->setSize(100);
        $this->title->setRule('/^[^'.$separator.']*$/');

        $this->description = new SmallText();
        $this->description->setHelper('Please provide a brief description of the article.');
        $this->description->setSize(200);
        $this->description->setRule("/\w+/");
        $this->bodyOnload = new SmallText();
        $this->content = new LargeText();
        $this->headerContent = new Text();
        $this->author = new SmallText();
        $this->author->setHelper('Please state the name of the author of this article');
        $this->author->setSize(70);
        $this->author->setRule("/\w+/");
        $this->published = new Boolean(0);

        $this->comments = new Relation();
        $this->markTransient('comments');

        $this->votes = new Relation();
        $this->markTransient('votes');

        $this->tags = new Relation();
        $this->markTransient('tags');

        $this->URL = '';
        $this->printURL = '';
        // mark the URL attributes as transient
        $this->markTransient('URL');
        $this->markTransient('printURL');

        // mark title as unique
        $this->markUnique('title');

        $this->markTransient('filePath');
        $this->markTransient('taggedAttributes');

        $this->setupRels();
    }

    /**
     * After creating a new Article, tokenize the description field to form a set
     * of automated tags and save them.
     *
     * @since 1.0
     */
    protected function afterSave(): void
    {
        if ($this->getVersion() == 1 && $this->tags instanceof \Alpha\Model\Type\Relation) {
            // update the empty tags values to reference this ID
            $this->tags->setValue($this->ID);

            foreach ($this->taggedAttributes as $tagged) {
                $tags = Tag::tokenize($this->get($tagged), 'Alpha\Model\Article', $this->getID());
                foreach ($tags as $tag) {
                    try {
                        $tag->save();
                    } catch (ValidationException $e) {
                        /*
                         * The unique key has most-likely been violated because this Record is already tagged with this
                         * value, so we can ignore in this case.
                         */
                    }
                }
            }
        }

        $this->setupRels();
    }

    /**
     * Set up the transient URL attributes for the artcile after it has loaded.
     *
     * @since 1.0
     */
    protected function afterLoadByAttribute(): void
    {
        $this->{'afterLoad'}();
    }

    /**
     * Set up the transient URL attributes for the article after it has loaded.
     *
     * @since 1.0
     */
    protected function afterLoad(): void
    {
        $config = ConfigProvider::getInstance();

        $this->URL = $config->get('app.url').'/a/'.str_replace(' ', $config->get('cms.url.title.separator'), $this->title->getValue());

        $this->printURL = $config->get('app.url').'/a/'.str_replace(' ', $config->get('cms.url.title.separator'), $this->title->getValue()).'/print';

        $this->setupRels();
    }

    /**
     * Gets an array of the IDs of the most recent articles added to the system (by date), from the newest
     * article to the amount specified by the $limit.
     *
     * @param int    $limit
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function loadRecentWithLimit(int $limit): array
    {
        $sqlQuery = 'SELECT ID FROM '.$this->getTableName()." WHERE published='1' ORDER BY created_ts DESC LIMIT 0, $limit;";

        $result = $this->query($sqlQuery);

        $IDs = array();

        foreach ($result as $row) {
            array_push($IDs, $row['ID']);
        }

        return $IDs;
    }

    /**
     * Generates the location of the attachments folder for this article.
     *
     * @since 1.0
     */
    public function getAttachmentsLocation(): string
    {
        $config = ConfigProvider::getInstance();

        return $config->get('app.file.store.dir').'attachments/article_'.$this->getID();
    }

    /**
     * Generates the URL of the attachments folder for this article.
     *
     * @since 1.0
     */
    public function getAttachmentsURL(): string
    {
        $config = ConfigProvider::getInstance();

        return $config->get('app.url').'/attachments/article_'.$this->getID();
    }

    /**
     * Generates a secure URL for downloading an attachment file via the ViewAttachment controller.
     *
     * @param string $filename
     *
     * @since 1.0
     */
    public function getAttachmentSecureURL(string $filename): string
    {
        return FrontController::generateSecureURL('act=Alpha\\Controller\\AttachmentController&articleID='.$this->getID().'&filename='.$filename);
    }

    /**
     * Creates the attachment folder for the article on the server.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function createAttachmentsFolder(): void
    {
        // create the attachment directory for the article
        try {
            mkdir($this->getAttachmentsLocation());
        } catch (\Exception $e) {
            throw new AlphaException('Unable to create the folder ['.$this->getAttachmentsLocation().'] for the article.');
        }

        // ...and set write permissions on the folder
        try {
            chmod($this->getAttachmentsLocation(), 0777);
        } catch (\Exception $e) {
            throw new AlphaException('Unable to set write permissions on the folder ['.$this->getAttachmentsLocation().'].');
        }
    }

    /**
     * Method for returning the calculated score for this article.
     *
     * @since 1.0
     */
    public function getArticleScore(): string
    {
        $votes = $this->getArticleVotes();

        $score = 0;
        $total_score = 0;
        $vote_count = count($votes);

        for ($i = 0; $i < $vote_count; ++$i) {
            $total_score += $votes[$i]->get('score');
        }

        if ($vote_count > 0) {
            $score = $total_score/$vote_count;
        }

        return sprintf('%01.2f', $score);
    }

    /**
     * Method for fetching all of the votes for this article. Returns an array of ArticleVote objects.
     *
     * @since 1.0
     */
    public function getArticleVotes(): array
    {
        $votes = $this->votes->getRelated();

        return $votes;
    }

    /**
     * Method to determine if the logged-in user has already voted for this article. Returns true if they have voted already, false otherwise.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function checkUserVoted(): bool
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');
        // just going to return true if nobody is logged in
        if ($session->get('currentUser') == null) {
            return true;
        }

        $userID = $session->get('currentUser')->getID();

        $vote = new ArticleVote();

        $sqlQuery = 'SELECT COUNT(*) AS usersVote FROM '.$vote->getTableName()." WHERE articleID='".$this->ID."' AND personID='".$userID."';";

        $result = $this->query($sqlQuery);

        if (!isset($result[0])) {
            throw new AlphaException('Failed to check if the current user voted for the article ['.$this->ID.'], query ['.$sqlQuery.']');
        }

        $row = $result[0];

        if ($row['usersVote'] == '0') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Method for fetching all of the comments for this article. Return an array of ArticleComment objects.
     *
     * @since 1.0
     */
    public function getArticleComments(): array
    {
        $comments = $this->comments->getRelated();

        return $comments;
    }

    /**
     * Loads the content of the ArticleObject from the specified file path.
     *
     * @param string $filePath
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\FileNotFoundException
     */
    public function loadContentFromFile(string $filePath): void
    {
        try {
            $this->content->setValue(file_get_contents($filePath));
            $this->filePath = $filePath;
        } catch (\Exception $e) {
            throw new FileNotFoundException($e->getMessage());
        }
    }

    /**
     * Returns true if the article content was loaded from a .text file, false otherwise.
     *
     * @since 1.0
     */
    public function isLoadedFromFile(): bool
    {
        return $this->filePath == '' ? false : true;
    }

    /**
     * Returns the timestamp of when the content .text file for this article was last
     * modified.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\FileNotFoundException
     */
    public function getContentFileDate(): string
    {
        if ($this->filePath != '') {
            try {
                return date('Y-m-d H:i:s', filemtime($this->filePath));
            } catch (\Exception $e) {
                throw new FileNotFoundException($e->getMessage());
            }
        } else {
            throw new FileNotFoundException('Error trying to access an article content file when none is set!');
        }
    }

    /**
     * Sets up the Relation definitions on this record object.
     *
     * @since 2.0
     */
    protected function setupRels(): void
    {
        $this->comments->setValue($this->ID);
        $this->comments->setRelatedClass('Alpha\Model\ArticleComment');
        $this->comments->setRelatedClassField('articleID');
        $this->comments->setRelatedClassDisplayField('content');
        $this->comments->setRelationType('ONE-TO-MANY');

        $this->votes->setValue($this->ID);
        $this->votes->setRelatedClass('Alpha\Model\ArticleVote');
        $this->votes->setRelatedClassField('articleID');
        $this->votes->setRelatedClassDisplayField('score');
        $this->votes->setRelationType('ONE-TO-MANY');

        $this->tags->setRelatedClass('Alpha\Model\Tag');
        $this->tags->setRelatedClassField('taggedID');
        $this->tags->setRelatedClassDisplayField('content');
        $this->tags->setRelationType('ONE-TO-MANY');
        $this->tags->setTaggedClass(get_class($this));
        $this->tags->setValue($this->ID);
    }
}
