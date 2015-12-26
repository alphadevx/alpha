<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Rights;
use Alpha\Model\Person;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Exception\FailedIndexCreateException;
use Alpha\Exception\FailedLookupCreateException;
use Alpha\Controller\Front\FrontController;
use Alpha\View\View;

/**
 * Controller used install the database.
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
class InstallController extends Controller implements ControllerInterface
{
    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * the constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('InstallController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        parent::__construct('Public');

        // set up the title and meta details
        $this->setTitle('Installing '.$config->get('app.title'));

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        // if there is nobody logged in, we will send them off to the Login controller to do so before coming back here
        if ($session->get('currentUser') === false) {
            self::$logger->info('Nobody logged in, invoking Login controller...');

            $controller = new LoginController();
            $controller->setName('LoginController');
            $controller->setRequest($request);
            $controller->setUnitOfWork(array('Alpha\Controller\LoginController', 'Alpha\Controller\InstallController'));

            self::$logger->debug('<<__construct');

            return $controller->doGET($request);
        }

        $params = $request->getParams();

        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $body = View::displayPageHead($this);

        $body .= '<h1>Installing the '.$config->get('app.title').' application</h1>';

        try {
            $body .= $this->createApplicationDirs();
        } catch (\Exception $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            $body .= View::displayErrorMessage('Aborting.');

            return new Response(500, $body, array('Content-Type' => 'text/html'));
        }

        // start a new database transaction
        ActiveRecord::begin();

        /*
         * Create DEnum tables
         */
        $DEnum = new DEnum();
        $DEnumItem = new DEnumItem();

        try {
            $body .= '<p>Attempting to create the DEnum tables...';
            if (!$DEnum->checkTableExists()) {
                $DEnum->makeTable();
            }
            self::$logger->info('Created the ['.$DEnum->getTableName().'] table successfully');

            if (!$DEnumItem->checkTableExists()) {
                $DEnumItem->makeTable();
            }
            self::$logger->info('Created the ['.$DEnumItem->getTableName().'] table successfully');

            // create a default article DEnum category
            $DEnum = new DEnum('Alpha\Model\Article::section');
            $DEnumItem = new DEnumItem();
            $DEnumItem->set('value', 'Main');
            $DEnumItem->set('DEnumID', $DEnum->getID());
            $DEnumItem->save();

            $body .= View::displayUpdateMessage('DEnums set up successfully.');
        } catch (\Exception $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            $body .= View::displayErrorMessage('Aborting.');
            self::$logger->error($e->getMessage());
            ActiveRecord::rollback();

            return new Response(500, $body, array('Content-Type' => 'text/html'));
        }

        /*
         * Loop over each business object in the system, and create a table for it
         */
        $classNames = ActiveRecord::getBOClassNames();
        $loadedClasses = array();

        foreach ($classNames as $classname) {
            array_push($loadedClasses, $classname);
        }

        foreach ($loadedClasses as $classname) {
            try {
                $body .= '<p>Attempting to create the table for the class ['.$classname.']...';

                try {
                    $BO = new $classname();

                    if (!$BO->checkTableExists()) {
                        $BO->makeTable();
                    } else {
                        if ($BO->checkTableNeedsUpdate()) {
                            $missingFields = $BO->findMissingFields();

                            $count = count($missingFields);

                            for ($i = 0; $i < $count; ++$i) {
                                $BO->addProperty($missingFields[$i]);
                            }
                        }
                    }
                } catch (FailedIndexCreateException $eice) {
                    // this are safe to ignore for now as they will be auto-created later once all of the tables are in place
                    self::$logger->warn($eice->getMessage());
                } catch (FailedLookupCreateException $elce) {
                    // this are safe to ignore for now as they will be auto-created later once all of the tables are in place
                    self::$logger->warn($elce->getMessage());
                }

                self::$logger->info('Created the ['.$BO->getTableName().'] table successfully');
                $body .= View::displayUpdateMessage('Created the ['.$BO->getTableName().'] table successfully');
            } catch (\Exception $e) {
                $body .= View::displayErrorMessage($e->getMessage());
                $body .= View::displayErrorMessage('Aborting.');
                self::$logger->error($e->getMessage());
                ActiveRecord::rollback();

                return new Response(500, $body, array('Content-Type' => 'text/html'));
            }
        }

        $body .= View::displayUpdateMessage('All business object tables created successfully!');

        /*
         * Create the Admin and Standard groups
         */
        $adminGroup = new Rights();
        $adminGroup->set('name', 'Admin');
        $standardGroup = new Rights();
        $standardGroup->set('name', 'Standard');

        try {
            try {
                $body .= '<p>Attempting to create the Admin and Standard groups...';
                $adminGroup->save();
                $standardGroup->save();

                self::$logger->info('Created the Admin and Standard rights groups successfully');
                $body .= View::displayUpdateMessage('Created the Admin and Standard rights groups successfully');
            } catch (FailedIndexCreateException $eice) {
                // this are safe to ignore for now as they will be auto-created later once all of the tables are in place
                self::$logger->warn($eice->getMessage());
            } catch (FailedLookupCreateException $elce) {
                // this are safe to ignore for now as they will be auto-created later once all of the tables are in place
                self::$logger->warn($elce->getMessage());
            }
        } catch (\Exception $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            $body .= View::displayErrorMessage('Aborting.');
            self::$logger->error($e->getMessage());
            ActiveRecord::rollback();

            return new Response(500, $body, array('Content-Type' => 'text/html'));
        }

        /*
         * Save the admin user to the database in the right group
         */
        try {
            try {
                $body .= '<p>Attempting to save the Admin account...';
                $admin = new Person();
                $admin->set('displayName', 'Admin');
                $admin->set('email', $session->get('currentUser')->get('email'));
                $admin->set('password', $session->get('currentUser')->get('password'));
                $admin->save();
                self::$logger->info('Created the admin user account ['.$session->get('currentUser')->get('email').'] successfully');

                $adminGroup->loadByAttribute('name', 'Admin');

                $lookup = $adminGroup->getMembers()->getLookup();
                $lookup->setValue(array($admin->getID(), $adminGroup->getID()));
                $lookup->save();

                self::$logger->info('Added the admin account to the Admin group successfully');
                $body .= View::displayUpdateMessage('Added the admin account to the Admin group successfully');
            } catch (FailedIndexCreateException $eice) {
                // this are safe to ignore for now as they will be auto-created later once all of the tables are in place
                self::$logger->warn($eice->getMessage());
            } catch (FailedLookupCreateException $elce) {
                // this are safe to ignore for now as they will be auto-created later once all of the tables are in place
                self::$logger->warn($elce->getMessage());
            }
        } catch (\Exception $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            $body .= View::displayErrorMessage('Aborting.');
            self::$logger->error($e->getMessage());
            ActiveRecord::rollback();

            return new Response(500, $body, array('Content-Type' => 'text/html'));
        }

        $body .= '<br><p align="center"><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ListActiveRecordsController').'">Administration Home Page</a></p><br>';
        $body .= View::displayPageFoot($this);

        // commit
        ActiveRecord::commit();

        self::$logger->info('Finished installation!');
        self::$logger->action('Installed the application');
        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Create the directories required by the application.
     *
     * @return string
     *
     * @since 2.0
     */
    public function createApplicationDirs()
    {
        self::$logger->debug('>>createApplicationDirs()');

        $config = ConfigProvider::getInstance();

        $body = '';

        // set the umask first before attempt mkdir
        umask(0);

        /*
         * Create the logs directory, then instantiate a new logger
         */
        $logsDir = $config->get('app.file.store.dir').'logs';

        $body .= '<p>Attempting to create the logs directory <em>'.$logsDir.'</em>...';

        if (!file_exists($logsDir)) {
            var_dump(mkdir($logsDir, 0774));
        }

        self::$logger = new Logger('InstallController');
        self::$logger->info('Started installation process!');
        self::$logger->info('Logs directory ['.$logsDir.'] successfully created');
        $body .= View::displayUpdateMessage('Logs directory ['.$logsDir.'] successfully created');

        /*
         * Create the src directory and sub-directories
         */
        $srcDir = $config->get('app.root').'src';

        $body .= '<p>Attempting to create the src directory <em>'.$srcDir.'</em>...';

        if (!file_exists($srcDir)) {
            mkdir($srcDir, 0774);
        }

        self::$logger->info('Source directory ['.$srcDir.'] successfully created');
        $body .= View::displayUpdateMessage('Source directory ['.$srcDir.'] successfully created');

        $srcDir = $config->get('app.root').'src/Model';

        if (!file_exists($srcDir)) {
            mkdir($srcDir, 0774);
        }

        self::$logger->info('Source directory ['.$srcDir.'] successfully created');
        $body .= View::displayUpdateMessage('Source directory ['.$srcDir.'] successfully created');

        $srcDir = $config->get('app.root').'src/View';

        if (!file_exists($srcDir)) {
            mkdir($srcDir, 0774);
        }

        self::$logger->info('Source directory ['.$srcDir.'] successfully created');
        $body .= View::displayUpdateMessage('Source directory ['.$srcDir.'] successfully created');

        /*
         * Create the attachments directory
         */
        $attachmentsDir = $config->get('app.file.store.dir').'attachments';

        $body .= '<p>Attempting to create the attachments directory <em>'.$attachmentsDir.'</em>...';

        if (!file_exists($attachmentsDir)) {
            mkdir($attachmentsDir, 0774);
        }

        self::$logger->info('Attachments directory ['.$attachmentsDir.'] successfully created');
        $body .= View::displayUpdateMessage('Attachments directory ['.$attachmentsDir.'] successfully created');

        /*
         * Create the cache directory and sub-directories
         */
        $cacheDir = $config->get('app.file.store.dir').'cache';
        $htmlDir = $config->get('app.file.store.dir').'cache/html';
        $imagesDir = $config->get('app.file.store.dir').'cache/images';
        $pdfDir = $config->get('app.file.store.dir').'cache/pdf';
        $xlsDir = $config->get('app.file.store.dir').'cache/xls';

        // cache
        $body .= '<p>Attempting to create the cache directory <em>'.$cacheDir.'</em>...';
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0774);
        }

        self::$logger->info('Cache directory ['.$cacheDir.'] successfully created');
        $body .= View::displayUpdateMessage('Cache directory ['.$cacheDir.'] successfully created');

        // cache/html
        $body .= '<p>Attempting to create the HTML cache directory <em>'.$htmlDir.'</em>...';
        if (!file_exists($htmlDir)) {
            mkdir($htmlDir, 0774);
        }

        self::$logger->info('Cache directory ['.$htmlDir.'] successfully created');
        $body .= View::displayUpdateMessage('Cache directory ['.$htmlDir.'] successfully created');

        // cache/images
        $body .= '<p>Attempting to create the cache directory <em>'.$imagesDir.'</em>...';
        if (!file_exists($imagesDir)) {
            mkdir($imagesDir, 0774);
        }

        self::$logger->info('Cache directory ['.$imagesDir.'] successfully created');
        $body .= View::displayUpdateMessage('Cache directory ['.$imagesDir.'] successfully created');

        // cache/pdf
        $body .= '<p>Attempting to create the cache directory <em>'.$pdfDir.'</em>...';
        if (!file_exists($pdfDir)) {
            mkdir($pdfDir, 0774);
        }

        self::$logger->info('Cache directory ['.$pdfDir.'] successfully created');
        $body .= View::displayUpdateMessage('Cache directory ['.$pdfDir.'] successfully created');

        // cache/xls
        $body .= '<p>Attempting to create the cache directory <em>'.$xlsDir.'</em>...';
        if (!file_exists($xlsDir)) {
            mkdir($xlsDir, 0774);
        }

        self::$logger->info('Cache directory ['.$xlsDir.'] successfully created');
        $body .= View::displayUpdateMessage('Cache directory ['.$xlsDir.'] successfully created');

        self::$logger->debug('<<createApplicationDirs');

        return $body;
    }

    /**
     * Custom version of the check rights method that only checks for a session for the config admin username/password,
     * when the system database is not set-up.
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

        if ($this->getVisibility() == 'Public') {
            self::$logger->debug('<<checkRights [true]');

            return true;
        }

        if (ActiveRecord::isInstalled()) {
            self::$logger->debug('<<checkRights [false]');

            return false;
        }

        // the person is logged in?
        if ($session->get('currentUser') !== false) {
            if ($session->get('currentUser')->get('email') == $config->get('app.install.username')) {
                self::$logger->debug('<<checkRights [true]');

                return true;
            }
        }
    }
}
