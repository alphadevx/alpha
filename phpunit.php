<?php

require __DIR__.'/vendor/autoload.php';

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

use Alpha\Util\Config\ConfigProvider;

$config = ConfigProvider::getInstance();

$dirs = array(
	$config->get('app.file.store.dir').'logs',
	$config->get('app.file.store.dir').'cache',
	$config->get('app.file.store.dir').'cache/html',
	$config->get('app.file.store.dir').'cache/images',
	$config->get('app.file.store.dir').'cache/pdf',
	$config->get('app.file.store.dir').'cache/xls',
	$config->get('app.file.store.dir').'attachments',
	);

foreach ($dirs as $dir) {
	if (!file_exists($dir)) {
		mkdir($dir, 0774);
	}
}

?>