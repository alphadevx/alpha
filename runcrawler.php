<?php

require __DIR__.'/vendor/autoload.php';

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

use Alpha\Task\CrawlTask;
use Alpha\Util\Config\ConfigProvider;

$config = ConfigProvider::getInstance();
$config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

$task = new CrawlTask();
$task->doTask();
