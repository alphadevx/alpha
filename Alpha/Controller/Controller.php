<?php

namespace Alpha\Controller;

use Alpha\Model\Type\Timestamp;
use Alpha\Model\Type\Integer;
use Alpha\Model\ActiveRecord;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Http\Session\SessionProviderFactory;
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
     * @var Alpha\Model\ActiveRecord
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
     * @var Alpha\Model\Type\Timestamp
     *
     * @since 1.0
     */
    protected $unitStartTime;

    /**
     * Stores the end time of a unit of work transaction.
     *
     * @var Alpha\Model\Type\Timestamp
     *
     * @since 1.0
     */
    protected $unitEndTime;

    /**
     * Stores the maximum allowed time duration (in seconds) of the unit of work.
     *
     * @var Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    protected $unitMAXDuration;

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
     * have no OID yet).
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
     * @var Alpha\Util\Http\Request
     *
     * @since 2.0
     */
    protected $request;

    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
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
    public function __construct($visibility = 'Public')
    {
        self::$logger = new Logger('Controller');
        self::$logger->debug('>>__construct(visibility=['.$visibility.'])');

        $config = ConfigProvider::getInstance();

        // set the access rights to the group name indicated
        $this->visibility = $visibility;

        $this->unitStartTime = new Timestamp(date('Y-m-d H:i:s'));
        $this->unitEndTime = new Timestamp();
        $this->unitMAXDuration = new Integer();

        // uses controller class name as the job name
        if ($this->name == '') {
            $this->setName(get_class($this));
        }

        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

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
     * @return mixed
     *
     * @since 1.0
     */
    public function getRecord()
    {
        self::$logger->debug('>>getRecord()');
        self::$logger->debug('<<getRecord ['.var_export($this->record, true).']');

        return $this->record;
    }

    /**
     * Setter for the record for this controller.
     *
     * @param Alpha\Model\ActiveRecord $record
     *
     * @since 1.0
     */
    public function setRecord($record)
    {
        self::$logger->debug('>>setRecord(record=['.var_export($record, true).'])');
        $this->record = $record;

        // if the record has tags, use these as the meta keywords for this controller
        if ($this->record->isTagged()) {
            $tags = $this->record->getPropObject('tags')->getRelatedObjects();

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
     * @return string
     *
     * @since 1.0
     */
    public function getName()
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
    public function setName($name)
    {
        self::$logger->debug('>>setName(name=['.$name.'])');
        $this->name = $name;
        self::$logger->debug('<<setName');
    }

    /**
     * Get the name of the rights group that has access to this controller.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getVisibility()
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
    public function setVisibility($visibility)
    {
        self::$logger->debug('>>setVisibility(visibility=['.$visibility.'])');
        $this->visibility = $visibility;
        self::$logger->debug('<<setVisibility');
    }

    /**
     * Gets the name of the first job in this unit of work.
     *
     * @return string The fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function getFirstJob()
    {
        self::$logger->debug('>>getFirstJob()');
        self::$logger->debug('<<getFirstJob ['.$this->firstJob.']');

        return $this->firstJob;
    }

    /**
     * Gets the name of the next job in this unit of work.
     *
     * @return string The fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function getNextJob()
    {
        self::$logger->debug('>>getNextJob()');
        self::$logger->debug('<<getNextJob ['.$this->nextJob.']');

        return $this->nextJob;
    }

    /**
     * Gets the name of the previous job in this unit of work.
     *
     * @return string The fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function getPreviousJob()
    {
        self::$logger->debug('>>getPreviousJob()');
        self::$logger->debug('<<getPreviousJob ['.$this->previousJob.']');

        return $this->previousJob;
    }

    /**
     * Gets the name of the last job in this unit of work.
     *
     * @return string The fully-qualified controller class name, or an absolute URL.
     *
     * @since 1.0
     */
    public function getLastJob()
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
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function setUnitOfWork($jobs)
    {
        self::$logger->debug('>>setUnitOfWork(jobs=['.var_export($jobs, true).'])');

        if (method_exists($this, 'before_setUnitOfWork_callback')) {
            $this->before_setUnitOfWork_callback();
        }

        if (!is_array($jobs)) {
            throw new IllegalArguementException('Bad $jobs array ['.var_export($jobs, true).'] passed to setUnitOfWork method!');
            self::$logger->debug('<<setUnitOfWork');

            return;
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
        $session = SessionProviderFactory::getInstance($sessionProvider);
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
                if (isset($jobs[$i - 1])) {
                    // set the previous job if it exists
                    $this->previousJob = $jobs[$i - 1];
                    self::$logger->debug('Previous job ['.$this->previousJob.']');
                }
                if (isset($jobs[$i + 1])) {
                    // set the next job if it exists
                    $this->nextJob = $jobs[$i + 1];
                    self::$logger->debug('Next job ['.$this->nextJob.']');
                }
            }
            // the last job in the sequence
            if ($i == ($numOfJobs - 1)) {
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

        if (method_exists($this, 'after_setUnitOfWork_callback')) {
            $this->after_setUnitOfWork_callback();
        }

        self::$logger->debug('<<setUnitOfWork');
    }

    /**
     * Getter for the unit start time.
     *
     * @return Timestamp
     *
     * @since 1.0
     */
    public function getStartTime()
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
    public function setUnitStartTime($year, $month, $day, $hour, $minute, $second)
    {
        self::$logger->debug('>>setUnitStartTime(year=['.$year.'], month=['.$month.'], day=['.$day.'], hour=['.$hour.'], minute=['.$minute.'],
	 		second=['.$second.'])');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $this->unitStartTime->setTimestampValue($year, $month, $day, $hour, $minute, $second);
        $session->set('unitStartTime', $this->unitStartTime->getValue());

        self::$logger->debug('<<setUnitStartTime');
    }

    /**
     * Getter for the unit end time.
     *
     * @return Alpha\Model\Type\Timestamp
     *
     * @since 1.0
     */
    public function getEndTime()
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
    public function setUnitEndTime($year, $month, $day, $hour, $minute, $second)
    {
        self::$logger->debug('>>setUnitEndTime(year=['.$year.'], month=['.$month.'], day=['.$day.'], hour=['.$hour.'], minute=['.$minute.'],
	 	 second=['.$second.'])');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $this->unitEndTime->setTimestampValue($year, $month, $day, $hour, $minute, $second);
        $session->set('unitEndTime', $this->unitEndTime->getValue());

        self::$logger->debug('<<setUnitEndTime');
    }

    /**
     * Getter for the unit of work MAX duration.
     *
     * @return Integer
     *
     * @since 1.0
     */
    public function getMAXDuration()
    {
        self::$logger->debug('>>getMAXDuration()');
        self::$logger->debug('<<getMAXDuration ['.$this->unitMAXDuration.']');

        return $this->unitMAXDuration;
    }

    /**
     * Setter for the unit MAX duration.
     *
     * @param int $duration The desired duration in seconds.
     *
     * @since 1.0
     */
    public function setUnitMAXDuration($duration)
    {
        self::$logger->debug('>>setUnitMAXDuration(duration=['.$duration.'])');
        $this->unitMAXDuration->setValue($duration);
        self::$logger->debug('<<setUnitMAXDuration');
    }

    /**
     * Calculates and returns the unit of work current duration in seconds.
     *
     * @return int
     *
     * @since 1.0
     */
    public function getUnitDuration()
    {
        self::$logger->debug('>>getUnitDuration()');

        $intStartTime = mktime(
            $this->unitStartTime->getHour(),
            $this->unitStartTime->getMinute(),
            $this->unitStartTime->getSecond(),
            $this->unitStartTime->getMonth(),
            $this->unitStartTime->getDay(),
            $this->unitStartTime->getYear()
            );

        $intEndTime = mktime(
            $this->unitEndTime->getHour(),
            $this->unitEndTime->getMinute(),
            $this->unitEndTime->getSecond(),
            $this->unitEndTime->getMonth(),
            $this->unitEndTime->getDay(),
            $this->unitEndTime->getYear()
            );

        self::$logger->debug('<<getUnitDuration ['.$intEndTime - $intStartTime.']');

        return $intEndTime - $intStartTime;
    }

    /**
     * Adds the supplied business object to the dirtyObjects array in the session.
     *
     * @param Alpha\Model\ActiveRecord $object
     *
     * @since 1.0
     */
    public function markDirty($object)
    {
        self::$logger->debug('>>markDirty(object=['.var_export($object, true).'])');

        if (method_exists($this, 'before_markDirty_callback')) {
            $this->before_markDirty_callback();
        }

        $this->dirtyObjects[count($this->dirtyObjects)] = $object;

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $session->set('dirtyObjects', $this->dirtyObjects);

        if (method_exists($this, 'after_markDirty_callback')) {
            $this->after_markDirty_callback();
        }

        self::$logger->debug('<<markDirty');
    }

    /**
     * Getter for the dirty objects array.
     *
     * @return array
     *
     * @since 1.0
     */
    public function getDirtyObjects()
    {
        self::$logger->debug('>>getDirtyObjects()');
        self::$logger->debug('<<getDirtyObjects ['.var_export($this->dirtyObjects, true).']');

        return $this->dirtyObjects;
    }

    /**
     * Adds a newly created business object to the newObjects array in the session.
     *
     * @param Alpha\Model\ActiveRecord $object
     *
     * @since 1.0
     */
    public function markNew($object)
    {
        self::$logger->debug('>>markNew(object=['.var_export($object, true).'])');

        if (method_exists($this, 'before_markNew_callback')) {
            $this->before_markNew_callback();
        }

        $this->newObjects[count($this->newObjects)] = $object;

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $session->set('newObjects', $this->newObjects);

        if (method_exists($this, 'after_markNew_callback')) {
            $this->after_markNew_callback();
        }

        self::$logger->debug('<<markNew');
    }

    /**
     * Getter for the new objects array.
     *
     * @return array
     *
     * @since 1.0
     */
    public function getNewObjects()
    {
        self::$logger->debug('>>getNewObjects()');
        self::$logger->debug('<<getNewObjects ['.var_export($this->newObjects, true).']');

        return $this->newObjects;
    }

    /**
     * Commits (saves) all of the new and modified (dirty) objects in the unit of work to the database.
     *
     * @throws FailedUnitCommitException
     *
     * @since 1.0
     */
    public function commit()
    {
        self::$logger->debug('>>commit()');

        if (method_exists($this, 'before_commit_callback')) {
            $this->before_commit_callback();
        }

        ActiveRecord::begin();

        $newObjects = $this->getNewObjects();

        $count = count($newObjects);

        for ($i = 0; $i < $count; ++$i) {
            try {
                $newObjects[$i]->save();
            } catch (FailedSaveException $e) {
                throw new FailedUnitCommitException($e->getMessage());
                self::$logger->error('Failed to save new object of type ['.get_class($newObjects[$i]).'], aborting...');
                $this->abort();

                return;
            } catch (LockingException $e) {
                throw new FailedUnitCommitException($e->getMessage());
                self::$logger->error('Failed to save new object of type ['.get_class($newObjects[$i]).'], aborting...');
                $this->abort();

                return;
            }
        }

        $dirtyObjects = $this->getDirtyObjects();

        $count = count($dirtyObjects);

        for ($i = 0; $i < $count; ++$i) {
            try {
                $dirtyObjects[$i]->save();
            } catch (FailedSaveException $e) {
                throw new FailedUnitCommitException($e->getMessage());
                self::$logger->error('Failed to save OID ['.$dirtyObjects[$i]->getID().'] of type ['.get_class($dirtyObjects[$i]).'], aborting...');
                $this->abort();

                return;
            } catch (LockingException $e) {
                throw new FailedUnitCommitException($e->getMessage());
                self::$logger->error('Failed to save OID ['.$dirtyObjects[$i]->getID().'] of type ['.get_class($dirtyObjects[$i]).'], aborting...');
                $this->abort();

                return;
            }
        }

        try {
            ActiveRecord::commit();

            $this->clearUnitOfWorkAttributes();

            if (method_exists($this, 'after_commit_callback')) {
                $this->after_commit_callback();
            }

            self::$logger->debug('<<commit');
        } catch (FailedSaveException $e) {
            throw new FailedUnitCommitException('Failed to commit the transaction, error is ['.$e->getMessage().']');
            self::$logger->debug('<<commit');
        }
    }

    /**
     * Method to clearup a cancelled unit of work.
     *
     * @throws Alpha\Exception\AlphaException
     *
     * @since 1.0
     */
    public function abort()
    {
        self::$logger->debug('>>abort()');

        if (method_exists($this, 'before_abort_callback')) {
            $this->before_abort_callback();
        }

        try {
            ActiveRecord::rollback();

            $this->clearUnitOfWorkAttributes();

            if (method_exists($this, 'after_abort_callback')) {
                $this->after_abort_callback();
            }

            self::$logger->debug('<<abort');
        } catch (AlphaException $e) {
            throw new AlphaException('Failed to rollback the transaction, error is ['.$e->getMessage().']');
            self::$logger->debug('<<abort');
        }
    }

    /**
     * Clears the session and object attributes related to unit of work sessions.
     *
     * @since 1.0
     */
    public function clearUnitOfWorkAttributes()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

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
     * @return string
     *
     * @since 1.0
     */
    public function getTitle()
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
    public function setTitle($title)
    {
        self::$logger->debug('>>setTitle(title=['.$title.'])');
        self::$logger->debug('<<setTitle');
        $this->title = $title;
    }

    /**
     * Getter for the page description.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getDescription()
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
    public function setDescription($description)
    {
        self::$logger->debug('>>setDescription(description=['.$description.'])');
        self::$logger->debug('<<setDescription');
        $this->description = $description;
    }

    /**
     * Getter for the page keywords.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getKeywords()
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
    public function setKeywords($keywords)
    {
        self::$logger->debug('>>setKeywords(keywords=['.$keywords.'])');
        self::$logger->debug('<<setKeywords');
        $this->keywords = $keywords;
    }

    /**
     * Method to return an access error for trespassing users.  HTTP response header code will be 403.
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function accessError()
    {
        self::$logger->debug('>>accessError()');

        if (method_exists($this, 'before_accessError_callback')) {
            $this->before_accessError_callback();
        }

        $config = ConfigProvider::getInstance();

        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        if ($session->get('currentUser') !== false) {
            self::$logger->warn('The user ['.$session->get('currentUser')->get('email').'] attempted to access the resource ['.$this->request->getURI().'] but was denied due to insufficient rights');
        } else {
            self::$logger->warn('An unknown user attempted to access the resource ['.$this->request->getURI().'] but was denied due to insufficient rights');
        }

        $response = new Response(403);
        $response->setBody(View::renderErrorPage(403, 'You do not have the correct access rights to view this page.  If you have not logged in yet, try going back to the home page and logging in from there.'));

        if (method_exists($this, 'after_accessError_callback')) {
            $this->after_accessError_callback();
        }

        self::$logger->debug('<<accessError');

        return $response;
    }

    /**
     * Checks the user rights of the currently logged-in person against the page
     * visibility set for this controller.  Will return false if the user has
     * not got the correct rights.
     *
     * @return bool
     *
     * @since 1.0
     */
    public function checkRights()
    {
        self::$logger->debug('>>checkRights()');

        $config = ConfigProvider::getInstance();

        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        if (method_exists($this, 'before_checkRights_callback')) {
            $this->before_checkRights_callback();
        }

        // firstly if the page is Public then there is no issue
        if ($this->getVisibility() == 'Public') {
            if (method_exists($this, 'after_checkRights_callback')) {
                $this->after_checkRights_callback();
            }

            self::$logger->debug('<<checkRights [true]');

            return true;
        } else {
            // the person is logged in?
            if ($session->get('currentUser') !== false) {

                // if the visibility is 'Session', just being logged in enough
                if ($this->getVisibility() == 'Session') {
                    if (method_exists($this, 'after_checkRights_callback')) {
                        $this->after_checkRights_callback();
                    }

                    self::$logger->debug('<<checkRights [true]');

                    return true;
                }

                // checking for admins (can access everything)
                if ($session->get('currentUser')->inGroup('Admin')) {
                    if (method_exists($this, 'after_checkRights_callback')) {
                        $this->after_checkRights_callback();
                    }

                    self::$logger->debug('<<checkRights [true]');

                    return true;
                } elseif ($session->get('currentUser')->inGroup($this->getVisibility())) {
                    if (method_exists($this, 'after_checkRights_callback')) {
                        $this->after_checkRights_callback();
                    }

                    self::$logger->debug('<<checkRights [true]');

                    return true;
                // the person is editing their own profile which is allowed
                } elseif (get_class($this->record) == 'Alpha\Model\Person' && $session->get('currentUser')->getDisplayName() == $this->record->getDisplayName()) {
                    if (method_exists($this, 'after_checkRights_callback')) {
                        $this->after_checkRights_callback();
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
     * @return bool
     *
     * @since 1.0
     */
    public function checkSecurityFields()
    {
        self::$logger->debug('>>checkSecurityFields()');

        $host = $this->request->getHost();
        $ip = $this->request->getIP();

        // the server hostname + today's date
        $var1 = base64_encode(SecurityUtils::encrypt($host.date('Ymd')));
        // the server's IP plus $var1
        $var2 = base64_encode(SecurityUtils::encrypt($ip.$var1));

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
            $var1 = base64_encode(SecurityUtils::encrypt($host.date('Ymd', (time() - 3600))));
            // the server's IP plus $var1
            $var2 = base64_encode(SecurityUtils::encrypt($ip.$var1));

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
     * Generates the two security fields to prevent remote form processing.
     *
     * @return array An array containing the two fields
     *
     * @since 1.0
     */
    public static function generateSecurityFields()
    {
        if (self::$logger == null) {
            self::$logger = new Logger('Controller');
        }
        self::$logger->debug('>>generateSecurityFields()');

        $request = new Request(array('method' => 'GET'));

        $host = $request->getHost();
        $ip = $request->getIP();

        // the server hostname + today's date
        $var1 = base64_encode(SecurityUtils::encrypt($host.date('Ymd')));
        // the server's IP plus $var1
        $var2 = base64_encode(SecurityUtils::encrypt($ip.$var1));

        self::$logger->debug('<<generateSecurityFields [array('.$var1.', '.$var2.')]');

        return array($var1, $var2);
    }

    /**
     * Returns the name of a custom controller if one is found, otherwise returns null.
     *
     * @param string $ActiveRecordType The classname of the active record
     *
     * @return string
     *
     * @since 1.0
     */
    public static function getCustomControllerName($ActiveRecordType)
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

            return;
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

            return;
        }
    }

    /**
     * Set the status message in the session to the value provided.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public function setStatusMessage($message)
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $this->statusMessage = $message;
        $session->set('statusMessage', $message);
    }

    /**
     * Gets the current status message for this controller.  Note that by getting the current
     * status message, you clear out the value stored in the session so this method can only be used
     * to get the status message once for display purposes.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getStatusMessage()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $session->delete('statusMessage');

        return $this->statusMessage;
    }

    /**
     * Checks that the definition for the controller classname provided exists.  Will also return true
     * if you pass "/" for the root of the web application.
     *
     * @param string $controllerName
     *
     * @return bool
     *
     * @since 1.0
     * @deprecated
     */
    public static function checkControllerDefExists($controllerName)
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
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public static function loadControllerDef($controllerName)
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
     * @return bool True if the current URL contains a tk value, false otherwise
     *
     * @since 1.0
     */
    public function checkIfAccessingFromSecureURL()
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
    private function decryptFieldNames($params)
    {
        $decrypted = array();

        foreach (array_keys($params) as $fieldname) {

            // set request params where fieldnames provided are based64 encoded and encrypted
            if (Validator::isBase64($fieldname)) {
                $decrypted[trim(SecurityUtils::decrypt(base64_decode($fieldname)))] = $params[$fieldname];
            }
        }
    }

    /**
     * Converts the supplied string to a "slug" that is URL safe and suitable for SEO.
     *
     * @param string $URLPart     The part of the URL to use as the slug
     * @param string $seperator   The URL seperator to use (default is -)
     * @param array  $filter      An optional array of charactors to filter out
     * @param bool   $crc32Prefix Set to true if you want to prefix the slug with the CRC32 hash of the URLPart supplied
     *
     * @return string A URL slug
     *
     * @since 1.2.4
     */
    public static function generateURLSlug($URLPart, $seperator = '-', $filter = array(), $crc32Prefix = false)
    {
        $URLPart = trim($URLPart);

        if (count($filter) > 0) {
            $URLPart = str_replace($filter, ' ', $URLPart);
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
     * @throws Alpha\Exception\NotImplementedException
     */
    public function doHEAD($request)
    {
        throw new NotImplementedException('The HEAD method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws Alpha\Exception\NotImplementedException
     */
    public function doGET($request)
    {
        throw new NotImplementedException('The GET method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws Alpha\Exception\NotImplementedException
     */
    public function doPOST($request)
    {
        throw new NotImplementedException('The POST method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws Alpha\Exception\NotImplementedException
     */
    public function doPUT($request)
    {
        throw new NotImplementedException('The PUT method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws Alpha\Exception\NotImplementedException
     */
    public function doPATCH($request)
    {
        throw new NotImplementedException('The PATCH method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     *
     * @throws Alpha\Exception\NotImplementedException
     */
    public function doDELETE($request)
    {
        throw new NotImplementedException('The DELETE method is not supported by this controller');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     */
    public function doOPTIONS($request)
    {
        $HTTPMethods = array('HEAD','GET','POST','PUT','PATCH','DELETE','OPTIONS');
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
     * Maps the supplied request with the appropiate method to run on this controller, for example
     * GET to doGET(), POST to doPOST() etc.  Returns the response generated by the method called.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 2.0
     */
    public function process($request)
    {
        if (!$request instanceof Request) {
            throw new IllegalArguementException('The request passed to process is not a valid Request object');
        }

        $config = ConfigProvider::getInstance();

        $method = $request->getMethod();

        if (in_array($method, array('POST', 'PUT', 'PATCH'))) {
            if ($config->get('security.encrypt.http.fieldnames')) {
                $decryptedParams = $this->decryptFieldNames($request->getParams());
                $request->setParams($decryptedParams);
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
        }

        return $response;
    }

    /**
     * Get the request this controller is processing (if any).
     *
     * @return Alpha\Util\Http\Request
     *
     * @since 2.0
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the request this controller is processing.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @since 2.0
     */
    public function setRequest($request)
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
     * @return string
     *
     * @since 1.2
     */
    public function after_displayPageHead_callback()
    {
        $accept = $this->request->getAccept();

        if ($accept != 'application/json') {
            $viewState = ViewState::getInstance();
            if ($viewState->get('renderAdminMenu') === true) {
                $menu = View::loadTemplateFragment('html', 'adminmenu.phtml', array());

                return $menu;
            }
        } else {
            return '';
        }
    }
}
