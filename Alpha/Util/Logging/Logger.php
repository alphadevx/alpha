<?php

namespace Alpha\Util\Logging;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Util\Http\Request;
use Alpha\Model\ActionLog;

/**
 * Log class used for debug and exception logging.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
class Logger
{
    /**
     * The log file the log entries will be saved to.
     *
     * @var \Alpha\Util\Logging\LogProviderFile
     *
     * @since 1.0
     */
    private $logProvider;

    /**
     * The logging level applied accross the system.  Valid options are DEBUG, INFO, WARN, ERROR, FATAL, and SQL.
     *
     * @var string
     *
     * @since 1.0
     */
    private $level;

    /**
     * The name of the class that this Logger is logging for.
     *
     * @var string
     *
     * @since 1.0
     */
    private $classname;

    /**
     * An array of class names that will be logged at debug level, regardless of the global Logger::level setting.
     *
     * @var array
     *
     * @since 1.0
     */
    private $debugClasses = array();

    /**
     * A request object that will give us the IP, user-agent etc. of the client we are logging for.
     *
     * @var \Alpha\Util\Http\Request
     *
     * @since 2.0
     */
    private $request;

    /**
     * The constructor.
     *
     * @param string $classname
     *
     * @since 1.0
     */
    public function __construct($classname)
    {
        $config = ConfigProvider::getInstance();

        $this->classname = $classname;
        $this->level = $config->get('app.log.trace.level');
        $this->debugClasses = explode(',', $config->get('app.log.trace.debug.classes'));
        $this->logProvider = ServiceFactory::getInstance('Alpha\Util\Logging\LogProviderFile', 'Alpha\Util\Logging\LogProviderInterface', true);
        $this->logProvider->setPath($config->get('app.file.store.dir').'logs/'.$config->get('app.log.file'));

        $this->request = new Request(array('method' => 'GET')); // hard-coding to GET here is fine as we don't log HTTP method (yet).
    }

    /**
     * Log a DEBUG message.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public function debug($message)
    {
        if ($this->level == 'DEBUG' || in_array($this->classname, $this->debugClasses)) {
            $dateTime = date('Y-m-d H:i:s');
            $this->logProvider->writeLine(array($dateTime, 'DEBUG', $this->classname, $message,
                $this->request->getUserAgent(), $this->request->getIP(), gethostname(), $this->request->getURI()));
        }
    }

    /**
     * Log an INFO message.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public function info($message)
    {
        if ($this->level == 'DEBUG' || $this->level == 'INFO' || in_array($this->classname, $this->debugClasses)) {
            $dateTime = date('Y-m-d H:i:s');
            $this->logProvider->writeLine(array($dateTime, 'INFO', $this->classname, $message,
                $this->request->getUserAgent(), $this->request->getIP(), gethostname(), $this->request->getURI()));
        }
    }

    /**
     * Log a WARN message.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public function warn($message)
    {
        if ($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN' || in_array($this->classname, $this->debugClasses)) {
            $dateTime = date('Y-m-d H:i:s');
            $this->logProvider->writeLine(array($dateTime, 'WARN', $this->classname, $message,
                $this->request->getUserAgent(), $this->request->getIP(), gethostname(), $this->request->getURI()));
        }
    }

    /**
     * Log an ERROR message.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public function error($message)
    {
        if ($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN' || $this->level == 'ERROR' ||
            in_array($this->classname, $this->debugClasses)) {
            $dateTime = date('Y-m-d H:i:s');
            $line = array($dateTime, 'ERROR', $this->classname, $message, $this->request->getUserAgent(), $this->request->getIP(), gethostname(), $this->request->getURI());
            $this->logProvider->writeLine($line);

            $this->notifyAdmin(print_r($line, true));
        }
    }

    /**
     * Log a FATAL message.
     *
     * @param string $message
     *
     * @since 1.0
     */
    public function fatal($message)
    {
        if ($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN' || $this->level == 'ERROR' ||
            $this->level == 'FATAL' || in_array($this->classname, $this->debugClasses)) {
            $dateTime = date('Y-m-d H:i:s');
            $line = array($dateTime, 'FATAL', $this->classname, $message, $this->request->getUserAgent(), $this->request->getIP(), gethostname(), $this->request->getURI());
            $this->logProvider->writeLine($line);

            $this->notifyAdmin(print_r($line, true));
        }
    }

    /**
     * Log a SQL queries.
     *
     * @param string $message
     *
     * @since 1.1
     */
    public function sql($message)
    {
        if ($this->level == 'SQL') {
            $dateTime = date('Y-m-d H:i:s');
            $line = array($dateTime, 'SQL', $this->classname, $message, $this->request->getUserAgent(), $this->request->getIP(), gethostname(), $this->request->getURI());
            $this->logProvider->writeLine($line);
        }
    }

    /**
     * Log an action carried out by a person to the ActionLog table.
     *
     * @param string $message
     *
     * @since 1.1
     */
    public function action($message)
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        if ($session->get('currentUser') != null && $config->get('app.log.action.logging')) {
            $action = new ActionLog();
            $action->set('client', $this->request->getUserAgent());
            $action->set('IP', $this->request->getIP());
            $action->set('message', $message);
            $action->save();
        }
    }

    /**
     * Notify the sys admin via email when a serious error occurs.
     *
     * @param string $message
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\MailNotSentException
     */
    public function notifyAdmin($message)
    {
        $config = ConfigProvider::getInstance();

        // just making sure an email address has been set in the .ini file
        if ($config->get('app.log.error.mail.address') != '') {
            $body = "The following error has occured:\n\n";

            $body .= 'Class:-> '.$this->classname."\n\n";
            $body .= 'Message:-> '.$message."\n\n";
            $body .= 'Server:-> '.gethostname()."\n\n";

            $body .= "\n\nKind regards,\n\nAdministrator\n--\n".$config->get('app.url');

            $mailer = ServiceFactory::getInstance('Alpha\Util\Email\EmailProviderPHP', 'Alpha\Util\Email\EmailProviderInterface');
            $mailer->send($config->get('app.log.error.mail.address'), $config->get('email.reply.to'), 'Error in class '.$this->classname.' on site '.$config->get('app.title'), $body);
        }
    }

    /**
     * Allows you to set the log file path to one other than the main application log.
     *
     * @param string $filepath
     *
     * @since 1.0
     */
    public function setLogProviderFile($filepath)
    {
        $config = ConfigProvider::getInstance();

        $this->logProvider = new LogProviderFile();
        $this->logProvider->setPath($filepath);
        $this->logProvider->setMaxSize($config->get('app.log.file.max.size'));
    }
}
