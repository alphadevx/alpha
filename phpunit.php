<?php

require __DIR__.'/vendor/autoload.php';

use Alpha\Util\Config\ConfigProvider;

$config = ConfigProvider::getInstance();

error_reporting($config->get('php.error.log.level'));

$dirs = array(
    $config->get('app.file.store.dir').'logs',
    $config->get('app.file.store.dir').'cache',
    $config->get('app.file.store.dir').'cache/files',
    $config->get('app.file.store.dir').'cache/images',
    $config->get('app.file.store.dir').'cache/xls',
    $config->get('app.file.store.dir').'attachments',
    );

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0774);
    }
}

ini_set('max_execution_time', 600);
