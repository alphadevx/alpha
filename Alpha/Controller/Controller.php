<?php

namespace Alpha\Controller;

use Alpha\Model\Type\Timestamp;
use Alpha\Model\Type\Integer;
use Alpha\Model\ActiveRecord;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Logging\Logger;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\FailedUnitCommitException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\LockingException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\NotImplementedException;
use Alpha\View\View;
use Alpha\View\ViewState;
use ReflectionClass;
use Exception;

/**
 * The master controller class for the Alpha Framework.
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
abstract class Controller
{
    /**
     * The name of the controller.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $name;

    /**
     * Used to set access privileages for the controller to the name of the rights group
     * allowed to access it.  'Public' by default.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $visibility = 'Public';

    /**
     * Optionally, the main record object that this controller is currently working with.
     *
     * @var \Alpha\Model\ActiveRecord
     *
     * @since 1.0
     */
    protected $record = null;

    /**
     * Used to determine if the controller is part of a unit of work sequence
     * (either empty or the name of the unit).
     *
     * @var string
     *
     * @since 1.0
     */
    protected $unitOfWork;

    /**
     * Stores the start time of a unit of work transaction.
     *
     * @var \Alpha\Model\Type\Timestamp
     *
     * @since 1.0
     */
    protected $unitStartTime;

    /**
     * Stores the end time of a unit of work transaction.
     *
     * @var \Alpha\Model\Type\Timestamp
     *
     * @since 1.0
     */
    protected $unitEndTime;

    /**
     * The name of the first controller that is used in this unit of work.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $firstJob;

    /**
     * The name of the next controller that is used in this unit of work.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $nextJob;

    /**
     * The name of the previous controller that is used in this unit of work.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $previousJob;

    /**
     * The name of the last controller that is used in this unit of work.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $lastJob;

    /**
     * An array for storing dirty record objects in a session (i.e. persistent business
     * objects that have not been updated in the database yet).
     *
     * @var array
     *
     * @since 1.0
     */
    protected $dirtyObjects = array();

    /**
     * An array for storing new reord objects in a session (transient business objects that
     * have no ID yet).
     *
     * @var array
     *
     * @since 1.0
     */
    protected $newObjects = array();

    /**
     * The title to be displayed on the controller page.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $title;

    /**
     * Meta keywords for the controller page, generally populated from tags.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $keywords;

    /**
     * Meta description for the controller page.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $description;

    /**
     * Used to set status update messages to display to the user (messages stored between requests
     * in session).  Useful for when you want to display a message to a user after POSTing a request,
     * or when moving from one page to the next.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $statusMessage;

    /**
     * The request that has been passed to this controller for processing.
     *
     * @var \Alpha\Util\Http\Request
     *
     * @since 2.0
     */
    protected $request;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor for the Controller that starts a new session if required, and handles
     * the population of new/dirty objects from the session when available.  Accepts the name
     * of the rights group that has access to this controller, 'Public' by default.
     *
     * @param string $visibility The name of the rights group that can access this controller.
     *
     * @since 1.0
     */
    public function __construct(string $visibility = 'Public')
    {
        self::$logger = new Logger('Controller');
        self::$logger->debug('>>__construct(visibility=['.$visibility.'])');

        $config = ConfigProvider::getInstance();

        // set the access rights to the group name indicated
        $this->visibility = $visibility;

        $this->unitStartTime = new Timestamp(date('Y-m-d H:i:s'));
        $this->unitEndTime = new Timestamp();

        // uses controller class name as the job name
        if ($this->name == '') {
            $this->setName(get_class($this));
        }

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        if ($session->get('unitOfWork') !== false && is_array($session->get('unitOfWork'))) {
            $this->setUnitOfWork($session->get('unitOfWork'));
        }

        if ($session->get('dirtyObjects') !== false && is_array($session->get('dirtyObjects'))) {
            $this->dirtyObjects = $session->get('dirtyObjects');
        }

        if ($session->get('newObjects') && is_array($session->get('newObjects'))) {
            $this->newObjects = $session->get('newObjects');
        }

        if ($session->get('statusMessage') !== false) {
            $this->setStatusMessage($session->get('statusMessage'));
        }

        self::$logger->debug('<<__construct');
    }

    /**
     * Get the record for this controller (if any).
     *
     * @since 1.0
     */
    public function getRecord(): \Alpha\Model\ActiveRecord|null
    {
        self::$logger->debug('>>getRecord()');
        self::$logger->debug('<<getRecord ['.var_export($this->record, true).']');

        return $this->record;
    }

    /**
     * Setter for the record for this controller.
     *
     * @param \Alpha\Model\ActiveRecord $record
     *
     * @since 1.0
     */
    public function setRecord(\Alpha\Model\ActiveRecord $record): void
    {
        self::$logger->debug('>>setRecord(record=['.var_export($record, true).'])');
        $this->record = $record;

        // if the record has tags, use these as the meta keywords for this controller
        if ($this->record->isTagged()) {
            $tags = $this->record->getPropObject('tags')->getRelated();

            $keywords = '';

            if (count($tags) > 0) {
                foreach ($tags as $tag) {
                    $keywords .= ','.$tag->get('content');
                }
            }

            $this->setKeywords(mb_substr($keywords, 1));
        }

        self::$logger->debug('<<setRecord');
    }

    /**
     * Get the name of the unit of work job.
     *
     * @since 1.0
     */
    public function getName(): string
    {
        self::$logger->debug('>>getName()');
        self::$logger->debug('<<getName ['.$this->name.']');

        return $this->name;
    }

    /**
     * Setter for the unit of work job name.
     *
     * @param string $name The fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function setName(string $name): void
    {
        self::$logger->debug('>>setName(name=['.$name.'])');
        $this->name = $name;
        self::$logger->debug('<<setName');
    }

    /**
     * Get the name of the rights group that has access to this controller.
     *
     * @since 1.0
     */
    public function getVisibility(): string
    {
        self::$logger->debug('>>getVisibility()');
        self::$logger->debug('<<getVisibility ['.$this->visibility.']');

        return $this->visibility;
    }

    /**
     * Setter for the name of the rights group that has access to this controller.
     *
     * @param string $visibility
     *
     * @since 1.0
     */
    public function setVisibility(string $visibility): void
    {
        self::$logger->debug('>>setVisibility(visibility=['.$visibility.'])');
        $this->visibility = $visibility;
        self::$logger->debug('<<setVisibility');
    }

    /**
     * Gets the name of the first job in this unit of work. Returns the fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function getFirstJob(): string|null
    {
        self::$logger->debug('>>getFirstJob()');
        self::$logger->debug('<<getFirstJob ['.$this->firstJob.']');

        return $this->firstJob;
    }

    /**
     * Gets the name of the next job in this unit of work. Returns the fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function getNextJob(): string|null
    {
        self::$logger->debug('>>getNextJob()');
        self::$logger->debug('<<getNextJob ['.$this->nextJob.']');

        return $this->nextJob;
    }

    /**
     * Gets the name of the previous job in this unit of work. Returns the fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function getPreviousJob(): string|null
    {
        self::$logger->debug('>>getPreviousJob()');
        self::$logger->debug('<<getPreviousJob ['.$this->previousJob.']');

        return $this->previousJob;
    }

    /**
     * Gets the name of the last job in this unit of work. Returns the fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function getLastJob(): string|null
    {
        self::$logger->debug('>>getLastJob()');
        self::$logger->debug('<<getLastJob ['.$this->lastJob.']');

        return $this->lastJob;
    }

    /**
     * Sets the name of the controller job sequence to the values in the supplied
     * array (and stores the array in the session).
     *
     * @param array $jobs The names of the controllers in this unit of work sequence.  Will accept fully-qualified controller class name, or an absolute URL.
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function setUnitOfWork(array $jobs): void
    {
        self::$logger->debug('>>setUnitOfWork(jobs=['.var_export($jobs, true).'])');

        if (method_exists($this, 'beforeSetUnitOfWork')) {
            $this->{'beforeSetUnitOfWork'}();
        }

        // validate that each controller name in the array actually exists
        foreach ($jobs as $job) {
            if (!Validator::isURL($job) && !class_exists($job)) {
                throw new IllegalArguementException('The controller name ['.$job.'] provided in the jobs array is not defined anywhere!');
            }
        }

        // clear out any previous unit of work from the session
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->delete('unitOfWork');
        $this->firstJob = null;
        $this->previousJob = null;
        $this->nextJob = null;
        $this->lastJob = null;
        $this->dirtyObjects = array();
        $this->newObjects = array();

        $numOfJobs = count($jobs);

        for ($i = 0; $i < $numOfJobs; ++$i) {
            // the first job in the sequence
            if ($i == 0) {
                $this->firstJob = $jobs[$i];
                self::$logger->debug('First job ['.$this->firstJob.']');
            }
            // found the current job
            if ($this->name == $jobs[$i]) {
                if (isset($jobs[$i-1])) {
                    // set the previous job if it exists
                    $this->previousJob = $jobs[$i-1];
                    self::$logger->debug('Previous job ['.$this->previousJob.']');
                }
                if (isset($jobs[$i+1])) {
                    // set the next job if it exists
                    $this->nextJob = $jobs[$i+1];
                    self::$logger->debug('Next job ['.$this->nextJob.']');
                }
            }
            // the last job in the sequence
            if ($i == ($numOfJobs-1)) {
                $this->lastJob = $jobs[$i];
            }
        }

        if ($this->previousJob == null) {
            $this->previousJob = $this->firstJob;
        }

        if ($this->nextJob == null) {
            $this->nextJob = $this->lastJob;
        }

        $session->set('unitOfWork', $jobs);

        if (method_exists($this, 'afterSetUnitOfWork')) {
            $this->{'afterSetUnitOfWork'}();
        }

        self::$logger->debug('<<setUnitOfWork');
    }

    /**
     * Getter for the unit start time.
     *
     * @since 1.0
     */
    public function getStartTime(): \Alpha\Model\Type\Timestamp
    {
        self::$logger->debug('>>getStartTime()');
        self::$logger->debug('<<getStartTime ['.$this->unitStartTime.']');

        return $this->unitStartTime;
    }

    /**
     * Setter for the unit start time (value will be stored in the session as key unitStartTime).
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @since 1.0
     */
    public function setUnitStartTime(int $year, int $month, int $day, int $hour, int $minute, int $second): void
    {
        self::$logger->debug('>>setUnitStartTime(year=['.$year.'], month=['.$month.'], day=['.$day.'], hour=['.$hour.'], minute=['.$minute.'],
            second=['.$second.'])');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $this->unitStartTime->setTimestampValue($year, $month, $day, $hour, $minute, $second);
        $session->set('unitStartTime', $this->unitStartTime->getValue());

        self::$logger->debug('<<setUnitStartTime');
    }

    /**
     * Getter for the unit end time.
     *
     * @since 1.0
     */
    public function getEndTime(): \Alpha\Model\Type\Timestamp
    {
        self::$logger->debug('>>getEndTime()');
        self::$logger->debug('<<getEndTime ['.$this->unitEndTime.']');

        return $this->unitEndTime;
    }

    /**
     * Setter for the unit end time (value will be stored in the session as key unitEndTime).
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @since 1.0
     */
    public function setUnitEndTime(int $year, int $month, int $day, int $hour, int $minute, int $second): void
    {
        self::$logger->debug('>>setUnitEndTime(year=['.$year.'], month=['.$month.'], day=['.$day.'], hour=['.$hour.'], minute=['.$minute.'],
         second=['.$second.'])');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $this->unitEndTime->setTimestampValue($year, $month, $day, $hour, $minute, $second);
        $session->set('unitEndTime', $this->unitEndTime->getValue());

        self::$logger->debug('<<setUnitEndTime');
    }

    /**
     * Calculates and returns the unit of work current duration in seconds.
     *
     * @since 1.0
     */
    public function getUnitDuration(): int
    {
        self::$logger->debug('>>getUnitDuration()');

        $intStartTime = mktime(
            intval($this->unitStartTime->getHour()),
            intval($this->unitStartTime->getMinute()),
            intval($this->unitStartTime->getSecond()),
            intval($this->unitStartTime->getMonth()),
            intval($this->unitStartTime->getDay()),
            intval($this->unitStartTime->getYear())
        );

        $intEndTime = mktime(
            intval($this->unitEndTime->getHour()),
            intval($this->unitEndTime->getMinute()),
            intval($this->unitEndTime->getSecond()),
            intval($this->unitEndTime->getMonth()),
            intval($this->unitEndTime->getDay()),
            intval($this->unitEndTime->getYear())
        );

        self::$logger->debug('<<getUnitDuration ['.($intEndTime-$intStartTime).']');

        return $intEndTime-$intStartTime;
    }

    /**
     * Adds the supplied business object to the dirtyObjects array in the session.
     *
     * @param \Alpha\Model\ActiveRecord $object
     *
     * @since 1.0
     */
    public function markDirty(\Alpha\Model\ActiveRecord $object): void
    {
        self::$logger->debug('>>markDirty(object=['.var_export($object, true).'])');

        if (method_exists($this, 'beforeMarkDirty')) {
            $this->{'beforeMarkDirty'}();
        }

        $this->dirtyObjects[count($this->dirtyObjects)] = $object;

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $session->set('dirtyObjects', $this->dirtyObjects);

        if (method_exists($this, 'afterMarkDirty')) {
            $this->{'afterMarkDirty'}();
        }

        self::$logger->debug('<<markDirty');
    }

    /**
     * Getter for the dirty objects array.
     *
     * @since 1.0
     */
    public function getDirtyObjects(): array
    {
        self::$logger->debug('>>getDirtyObjects()');
        self::$logger->debug('<<getDirtyObjects ['.var_export($this->dirtyObjects, true).']');

        return $this->dirtyObjects;
    }

    /**
     * Adds a newly created business object to the newObjects array in the session.
     *
     * @param \Alpha\Model\ActiveRecord $object
     *
     * @since 1.0
     */
    public function markNew(\Alpha\Model\ActiveRecord $object): void
    {
        self::$logger->debug('>>markNew(object=['.var_export($object, true).'])');

        if (method_exists($this, 'beforeMarkNew')) {
            $this->{'beforeMarkNew'}();
        }

        $this->newObjects[count($this->newObjects)] = $object;

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $session->set('newObjects', $this->newObjects);

        if (method_exists($this, 'afterMarkNew')) {
            $this->{'afterMarkNew'}();
        }

        self::$logger->debug('<<markNew');
    }

    /**
     * Getter for the new objects array.
     *
     * @since 1.0
     */
    public function getNewObjects(): array
    {
        self::$logger->debug('>>getNewObjects()');
        self::$logger->debug('<<getNewObjects ['.var_export($this->newObjects, true).']');

        return $this->newObjects;
    }

    /**
     * Commits (saves) all of the new and modified (dirty) objects in the unit of work to the database.
     *
     * @throws \Alpha\Exception\FailedUnitCommitException
     *
     * @since 1.0
     */
    public function commit(): void
    {
        self::$logger->debug('>>commit()');

        if (method_exists($this, 'beforeCommit')) {
            $this->{'beforeCommit'}();
        }

        ActiveRecord::begin();

        $newObjects = $this->getNewObjects();

        $count = count($newObjects);

        for ($i = 0; $i < $count; ++$i) {
            try {
                $newObjects[$i]->save();
            } catch (FailedSaveException $e) {
                self::$logger->error('Failed to save new object of type ['.get_class($newObjects[$i]).'], aborting...');
                $this->abort();

                throw new FailedUnitCommitException($e->getMessage());
            } catch (LockingException $e) {
                self::$logger->error('Failed to save new object of type ['.get_class($newObjects[$i]).'], aborting...');
                $this->abort();

                throw new FailedUnitCommitException($e->getMessage());
            }
        }

        $dirtyObjects = $this->getDirtyObjects();

        $count = count($dirtyObjects);

        for ($i = 0; $i < $count; ++$i) {
            try {
                $dirtyObjects[$i]->save();
            } catch (FailedSaveException $e) {
                self::$logger->error('Failed to save ID ['.$dirtyObjects[$i]->getID().'] of type ['.get_class($dirtyObjects[$i]).'], aborting...');
                $this->abort();

                throw new FailedUnitCommitException($e->getMessage());
            } catch (LockingException $e) {
                self::$logger->error('Failed to save ID ['.$dirtyObjects[$i]->getID().'] of type ['.get_class($dirtyObjects[$i]).'], aborting...');
                $this->abort();

                throw new FailedUnitCommitException($e->getMessage());
            }
        }

        try {
            ActiveRecord::commit();

            $this->clearUnitOfWorkAttributes();

            if (method_exists($this, 'afterCommit')) {
                $this->{'afterCommit'}();
            }

            self::$logger->debug('<<commit');
        } catch (FailedSaveException $e) {
            self::$logger->debug('<<commit');
            throw new FailedUnitCommitException('Failed to commit the transaction, error is ['.$e->getMessage().']');
        }
    }

    /**
     * Method to clearup a cancelled unit of work.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 1.0
     */
    public function abort(): void
    {
        self::$logger->debug('>>abort()');

        if (method_exists($this, 'beforeAbort')) {
            $this->{'beforeAbort'}();
        }

        try {
            ActiveRecord::rollback();

            $this->clearUnitOfWorkAttributes();

            if (method_exists($this, 'afterAbort')) {
                $this->{'afterAbort'}();
            }

            self::$logger->debug('<<abort');
        } catch (AlphaException $e) {
            self::$logger->debug('<<abort');
            throw new AlphaException('Failed to rollback the transaction, error is ['.$e->getMessage().']');
        }
    }

    /**
     * Clears the session and object attributes related to unit of work sessions.
     *
     * @since 1.0
     */
    public function clearUnitOfWorkAttributes(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $session->delete('unitOfWork');
        $this->unitOfWork = null;
        $session->delete('dirtyObjects');
        $this->dirtyObjects = array();
        $session->delete('newObjects');
        $this->newObjects = array();
    }

    /**
     * Getter for the page title.
     *
     * @since 1.0
     */
    public function getTitle(): string
    {
        self::$logger->debug('>>getTitle()');
        self::$logger->debug('<<getTitle ['.$this->title.']');

        return $this->title;
    }

    /**
     * Setter for the page title.
     *
     * @param string $title
     *
     * @since 1.0
     */
    public function setTitle(string $title): void
    {
        self::$logger->debug('>>setTitle(title=['.$title.'])');
        self::$logger->debug('<<setTitle');
        $this->title = $title;
    }

    /**
     * Getter for the page description.
     *
     * @since 1.0
     */
    public function getDescription(): string|null
    {
        self::$logger->debug('>>getDescription()');
        self::$logger->debug('<<getDescription ['.$this->description.']');

        return $this->description;
    }

    /**
     * Setter for the page description.
     *
     * @param string $description
     *
     * @since 1.0
     */
    public function setDescription(string $description): void
    {
        self::$logger->debug('>>setDescription(description=['.$description.'])');
        self::$logger->debug('<<setDescription');
        $this->description = $description;
    }

    /**
     * Getter for the page keywords.
     *
     * @since 1.0
     */
    public function getKeywords(): string|null
    {
        self::$logger->debug('>>getKeywords()');
        self::$logger->debug('<<getKeywords ['.$this->keywords.']');

        return $this->keywords;
    }

    /**
     * Setter for the page keywords, should pass a comma-seperated list as a string.
     *
     * @param string $keywords
     *
     * @since 1.0
     */
    public function setKeywords(string $keywords): void
    {
        self::$logger->debug('>>setKeywords(keywords=['.$keywords.'])');
        self::$logger->debug('<<setKeywords');
        $this->keywords = $keywords;
    }

    /**
     * Method to return an access error for trespassing users.  HTTP response header code will be 403.
     *
     * @since 1.0
     */
    public function accessError(): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>accessError()');

        if (method_exists($this, 'beforeAccessError')) {
            $this->{'beforeAccessError'}();
        }

        $config = ConfigProvider::getInstance();

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        if ($session->get('currentUser') !== false) {
            self::$logger->warn('The user ['.$session->get('currentUser')->get('email').'] attempted to access the resource ['.$this->request->getURI().'] but was denied due to insufficient rights');
        } else {
            self::$logger->warn('An unknown user attempted to access the resource ['.$this->request->getURI().'] but was denied due to insufficient rights');
        }

        $response = new Response(403);
        $response->setBody(View::renderErrorPage(403, 'You do not have the correct access rights to view this page.  If you have not logged in yet, try going back to the home page and logging in from there.'));

        if (method_exists($this, 'afterAccessError')) {
            $this->{'afterAccessError'}();
        }

        self::$logger->debug('<<accessError');

        return $response;
    }

    /**
     * Checks the user rights of the currently logged-in person against the page
     * visibility set for this controller.  Will return false if the user has
     * not got the correct rights.
     *
     * @since 1.0
     */
    public function checkRights(): bool
    {
        self::$logger->debug('>>checkRights()');

        $config = ConfigProvider::getInstance();

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        if (method_exists($this, 'beforeCheckRights')) {
            $this->{'beforeCheckRights'}();
        }

        // firstly if the page is Public then there is no issue
        if ($this->getVisibility() == 'Public') {
            if (method_exists($this, 'afterCheckRights')) {
                $this->{'afterCheckRights'}();
            }

            self::$logger->debug('<<checkRights [true]');

            return true;
        } else {
            // the person is logged in?
            if ($session->get('currentUser') !== false) {

                // if the visibility is 'Session', just being logged in enough
                if ($this->getVisibility() == 'Session') {
                    if (method_exists($this, 'afterCheckRights')) {
                        $this->{'afterCheckRights'}();
                    }

                    self::$logger->debug('<<checkRights [true]');

                    return true;
                }

                // checking for admins (can access everything)
                if ($session->get('currentUser')->inGroup('Admin')) {
                    if (method_exists($this, 'afterCheckRights')) {
                        $this->{'afterCheckRights'}();
                    }

                    self::$logger->debug('<<checkRights [true]');

                    return true;
                } elseif ($session->get('currentUser')->inGroup($this->getVisibility())) {
                    if (method_exists($this, 'afterCheckRights')) {
                        $this->{'afterCheckRights'}();
                    }

                    self::$logger->debug('<<checkRights [true]');

                    return true;
                // the person is editing their own profile which is allowed
                } elseif ((isset($this->record) && get_class($this->record) == 'Alpha\Model\Person') && $session->get('currentUser')->getUsername() == $this->record->getUsername()) {
                    if (method_exists($this, 'afterCheckRights')) {
                        $this->{'afterCheckRights'}();
                    }

                    self::$logger->debug('<<checkRights [true]');

                    return true;
                } else {
                    self::$logger->debug('<<checkRights [false]');

                    return false;
                }
            } else { // the person is NOT logged in
                self::$logger->debug('<<checkRights [false]');

                return false;
            }
        }
    }

    /**
     * Method to check the validity of the two hidden form security
     * fields which aim to ensure that a post to the controller is being sent from
     * the same server that is hosting it.
     *
     * @since 1.0
     */
    public function checkSecurityFields(): bool
    {
        self::$logger->debug('>>checkSecurityFields()');

        $host = $this->request->getHost();
        $ip = $this->request->getIP();

        // the server hostname + today's date
        $var1 = rtrim(strtr(base64_encode(SecurityUtils::encrypt($host.date('Ymd'))), '+/', '-_'), '=');
        // the server's IP plus $var1
        $var2 = rtrim(strtr(base64_encode(SecurityUtils::encrypt($ip.$var1)), '+/', '-_'), '=');

        if ($this->request->getParam('var1') === null || $this->request->getParam('var2') === null) {
            self::$logger->warn('The required var1/var2 params where not provided on the HTTP request');
            self::$logger->debug('<<checkSecurityFields [false]');

            return false;
        }

        if ($var1 == $this->request->getParam('var1') && $var2 == $this->request->getParam('var2')) {
            self::$logger->debug('<<checkSecurityFields [true]');

            return true;
        } else {
            /*
             * Here we are implementing a "grace period" of one hour if the time is < 1:00AM, we will accept
             * a match for yesterday's date in the security fields
             *
             */

            // the server hostname + today's date less 1 hour (i.e. yesterday where time is < 1:00AM)
            $var1 = rtrim(strtr(base64_encode(SecurityUtils::encrypt($host.date('Ymd', (time()-3600)))), '+/', '-_'), '=');
            // the server's IP plus $var1
            $var2 = rtrim(strtr(base64_encode(SecurityUtils::encrypt($ip.$var1)), '+/', '-_'), '=');

            if ($var1 == $this->request->getParam('var1') && $var2 == $this->request->getParam('var2')) {
                self::$logger->debug('<<checkSecurityFields [true]');

                return true;
            } else {
                self::$logger->warn('The var1/var2 params provided are invalid, values: var1=['.$this->request->getParam('var1').'] var2=['.$this->request->getParam('var2').']');
                self::$logger->debug('<<checkSecurityFields [false]');

                return false;
            }
        }
    }

    /**
     * Generates the two security fields to prevent remote form processing, returned as an array.
     *
     * @since 1.0
     */
    public static function generateSecurityFields(): array
    {
        if (self::$logger == null) {
            self::$logger = new Logger('Controller');
        }
        self::$logger->debug('>>generateSecurityFields()');

        $request = new Request(array('method' => 'GET'));

        $host = $request->getHost();
        $ip = $request->getIP();

        // the server hostname + today's date
        $var1 = rtrim(strtr(base64_encode(SecurityUtils::encrypt($host.date('Ymd'))), '+/', '-_'), '=');
        // the server's IP plus $var1
        $var2 = rtrim(strtr(base64_encode(SecurityUtils::encrypt($ip.$var1)), '+/', '-_'), '=');

        self::$logger->debug('<<generateSecurityFields [array('.$var1.', '.$var2.')]');

        return array($var1, $var2);
    }

    /**
     * Returns the name of a custom controller if one is found, otherwise returns null.
     *
     * @param string $ActiveRecordType The classname of the active record
     *
     * @since 1.0
     */
    public static function getCustomControllerName(string $ActiveRecordType): string|null
    {
        if (self::$logger == null) {
            self::$logger = new Logger('Controller');
        }
        self::$logger->debug('>>getCustomControllerName(ActiveRecordType=['.$ActiveRecordType.']');

        $config = ConfigProvider::getInstance();

        try {
            $class = new ReflectionClass($ActiveRecordType);
            $controllerName = $class->getShortname().'Controller';
        } catch (Exception $e) {
            self::$logger->warn('Bad active record name ['.$ActiveRecordType.'] passed to getCustomControllerName()');

            return null;
        }

        self::$logger->debug('Custom controller name is ['.$controllerName.']');

        if (file_exists($config->get('app.root').'Controller/'.$controllerName.'.php')) {
            $controllerName = 'Controller\\'.$controllerName;
            self::$logger->debug('<<getCustomControllerName ['.$controllerName.']');

            return $controllerName;
        } elseif (file_exists($config->get('app.root').'Alpha/Controller/'.$controllerName.'.php')) {
            $controllerName = 'Alpha\Controller\\'.$controllerName;
            self::$logger->debug('<<getCustomControllerName ['.$controllerName.']');

            return $controllerName;
        } else {
            self::$logger->debug('<<getCustomControllerName');

            return null;
        }
    }

    /**
     * Set the status message in the session to the value provided.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public function setStatusMessage(string $message): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $this->statusMessage = $message;
        $session->set('statusMessage', $message);
    }

    /**
     * Gets the current status message for this controller.  Note that by getting the current
     * status message, you clear out the value stored in the session so this method can only be used
     * to get the status message once for display purposes.
     *
     * @since 1.0
     */
    public function getStatusMessage(): string|null
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $session->delete('statusMessage');

        return $this->statusMessage;
    }

    /**
     * Checks that the definition for the controller classname provided exists.  Will also return true
     * if you pass "/" for the root of the web application.
     *
     * @param string $controllerName
     *
     * @since 1.0
     * @deprecated
     */
    public static function checkControllerDefExists(string $controllerName): bool
    {
        if (self::$logger == null) {
            self::$logger = new Logger('Controller');
        }
        self::$logger->debug('>>checkControllerDefExists(controllerName=['.$controllerName.'])');

        $config = ConfigProvider::getInstance();

        $exists = false;

        if ($controllerName == '/') {
            $exists = true;
        }
        if (file_exists($config->get('app.root').'Controller/'.$controllerName.'.php')) {
            $exists = true;
        }
        if (file_exists($config->get('app.root').'Alpha/Controller/'.$controllerName.'.php')) {
            $exists = true;
        }

        self::$logger->debug('<<checkControllerDefExists ['.$exists.']');

        return $exists;
    }

    /**
     * Loads the definition for the controller classname provided.
     *
     * @param string $controllerName
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public static function loadControllerDef(string $controllerName): void
    {
        if (self::$logger == null) {
            self::$logger = new Logger('Controller');
        }
        self::$logger->debug('>>loadControllerDef(controllerName=['.$controllerName.'])');

        $config = ConfigProvider::getInstance();

        if (file_exists($config->get('app.root').'Controller/'.$controllerName.'.php')) {
            require_once $config->get('app.root').'Controller/'.$controllerName.'.php';
        } elseif (file_exists($config->get('app.root').'Alpha/Controller/'.$controllerName.'.php')) {
            require_once $config->get('app.root').'Alpha/Controller/'.$controllerName.'.php';
        } else {
            throw new IllegalArguementException('The class ['.$controllerName.'] is not defined anywhere!');
        }

        self::$logger->debug('<<loadControllerDef');
    }

    /**
     * Method for determining if the current request URL is a secure one (has a tk string or not).
     *
     * @since 1.0
     */
    public function checkIfAccessingFromSecureURL(): bool
    {
        if ($this->request->getParam('tk') != null || mb_strpos($this->request->getURI(), '/tk/') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Descrypts the HTTP param fieldnames in the array provided and returns the plain version.
     *
     * @param $params array
     *
     * @since 1.2.2
     */
    private function decryptFieldNames($params): array
    {
        $decrypted = array();

        foreach (array_keys($params) as $fieldname) {

            // set request params where fieldnames provided are based64 encoded and encrypted
            if (Validator::isBase64($fieldname)) {
                $decrypted[SecurityUtils::decrypt(base64_decode($fieldname, true))] = $params[$fieldname];
            }
        }

        return $decrypted;
    }

    /**
     * Converts the supplied string to a "slug" that is URL safe and suitable for SEO.
     *
     * @param string $URLPart     The part of the URL to use as the slug
     * @param string $seperator   The URL seperator to use (default is -)
     * @param array  $filter      An optional array of charactors to filter out
     * @param bool   $crc32Prefix Set to true if you want to prefix the slug with the CRC32 hash of the URLPart supplied
     *
     * @since 1.2.4
     */
    public static function generateURLSlug(string $URLPart, string $seperator = '-', array $filter = array(), bool $crc32Prefix = false): string
    {
        $URLPart = trim($URLPart);

        if (count($filter) > 0) {
            $URLPart = str_replace($filter, '', $URLPart);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $URLPart);
        $clean = preg_replace("/[^a-zA-Z0-9\/\._|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\.\/_|+ -]+/", $seperator, $clean);

        if ($crc32Prefix) {
            $clean = hexdec(hash('crc32b', $URLPart)).$seperator.$clean;
        }

        return $clean;
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\NotImplementedException
     * @param Request $request
     */
    public function doHEAD(Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('doHEAD() called but not implement in child class, request URI ['.$request->getURI().']');
        throw new NotImplementedException('The HEAD method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\NotImplementedException
     * @param Request $request
     */
    public function doGET(Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('doGET() called but not implement in child class, request URI ['.$request->getURI().']');
        throw new NotImplementedException('The GET method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\NotImplementedException
     * @param Request $request
     */
    public function doPOST(Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('doPOST() called but not implement in child class, request URI ['.$request->getURI().']');
        throw new NotImplementedException('The POST method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\NotImplementedException
     * @param Request $request
     */
    public function doPUT(Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('doPUT() called but not implement in child class, request URI ['.$request->getURI().']');
        throw new NotImplementedException('The PUT method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\NotImplementedException
     * @param Request $request
     */
    public function doPATCH(Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('doPATCH() called but not implement in child class, request URI ['.$request->getURI().']');
        throw new NotImplementedException('The PATCH method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\NotImplementedException
     * @param Request $request
     */
    public function doDELETE(Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('doDELETE() called but not implement in child class, request URI ['.$request->getURI().']');
        throw new NotImplementedException('The DELETE method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     * @param Request $request
     */
    public function doOPTIONS(Request $request): \Alpha\Util\Http\Response
    {
        $HTTPMethods = array('HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');
        $supported = array();

        foreach ($HTTPMethods as $HTTPMethod) {
            $reflector = new \ReflectionMethod($this, 'do'.$HTTPMethod);
            $isOverridden = ($reflector->getDeclaringClass()->getName() === get_class($this));

            if ($isOverridden) {
                $supported[] = $HTTPMethod;
            }
        }

        $supported = implode(',', $supported);

        $response = new Response(200);
        $response->setHeader('Allow', $supported);

        return $response;
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.2
     * @param Request $request
     */
    public function doTRACE(Request $request): \Alpha\Util\Http\Response
    {
        $HTTPMethods = array('HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');
        $supported = array();

        foreach ($HTTPMethods as $HTTPMethod) {
            $reflector = new \ReflectionMethod($this, 'do'.$HTTPMethod);
            $isOverridden = ($reflector->getDeclaringClass()->getName() === get_class($this));

            if ($isOverridden) {
                $supported[] = $HTTPMethod;
            }
        }

        $supported = implode(',', $supported);

        $response = new Response(405);
        $response->setHeader('Allow', $supported);

        return $response;
    }

    /**
     * Maps the supplied request with the appropiate method to run on this controller, for example
     * GET to doGET(), POST to doPOST() etc.  Returns the response generated by the method called.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 2.0
     */
    public function process(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        if (!$request instanceof Request) {
            throw new IllegalArguementException('The request passed to process is not a valid Request object');
        }

        $config = ConfigProvider::getInstance();

        $method = $request->getMethod();

        if (in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
            if ($config->get('security.encrypt.http.fieldnames')) {
                $decryptedParams = $this->decryptFieldNames($request->getParams());
                $request->addParams($decryptedParams);

                if ($request->getParam('_METHOD') != null) {
                    $request->setMethod($request->getParam('_METHOD'));
                    $method = $request->getMethod();
                }
            }
        }

        $ProviderClassName = $config->get('app.renderer.provider.name');

        if ($ProviderClassName == 'auto' && $request->getAccept() != null) {
            View::setProvider('auto', $request->getAccept());
        }

        $this->request = $request;

        // check the current user's rights on access to the page controller
        if (!$this->checkRights()) {
            return $this->accessError();
        }

        switch ($method) {
            case 'HEAD':
                $response = $this->doHEAD($request);
            break;
            case 'GET':
                $response = $this->doGET($request);
            break;
            case 'POST':
                $response = $this->doPOST($request);
            break;
            case 'PUT':
                $response = $this->doPUT($request);
            break;
            case 'PATCH':
                $response = $this->doPATCH($request);
            break;
            case 'DELETE':
                $response = $this->doDELETE($request);
            break;
            case 'OPTIONS':
                $response = $this->doOPTIONS($request);
            break;
            case 'TRACE':
                $response = $this->doTRACE($request);
            break;
            default:
                $response = $this->doGET($request);
        }

        return $response;
    }

    /**
     * Get the request this controller is processing (if any).
     *
     * @since 2.0
     */
    public function getRequest(): \Alpha\Util\Http\Request
    {
        return $this->request;
    }

    /**
     * Set the request this controller is processing.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 2.0
     */
    public function setRequest(\Alpha\Util\Http\Request $request): void
    {
        if ($request instanceof Request) {
            $this->request = $request;
        } else {
            throw new IllegalArguementException('Invalid request object ['.print_r($request, true).'] passed');
        }
    }

    /**
     * Use this callback to inject in the admin menu template fragment.
     *
     * @since 1.2
     */
    public function afterDisplayPageHead(): string
    {
        $accept = $this->request->getAccept();

        if ($accept != 'application/json' && $this->checkIfAccessingFromSecureURL()) {
            $viewState = ViewState::getInstance();
            $menu = '';

            if ($viewState->get('renderAdminMenu')) {
                $config = ConfigProvider::getInstance();

                $sessionProvider = $config->get('session.provider.name');
                $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

                if ($session->get('currentUser') !== false) {
                    $passwordResetRequired = SecurityUtils::checkAdminPasswordIsDefault($session->get('currentUser')->get('password'));
                    $menu = View::loadTemplateFragment('html', 'adminmenu.phtml', array('passwordResetRequired' => $passwordResetRequired));
                } else {
                    $menu = '';
                }

                return $menu;
            }
        }

        return '';
    }
}
