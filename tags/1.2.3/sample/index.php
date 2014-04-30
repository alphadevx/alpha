<?php

// include the config file
require_once 'alpha/util/AlphaConfig.inc';
$config = AlphaConfig::getInstance();

require_once $config->get('app.root').'alpha/util/AlphaAutoLoader.inc';

$request = str_replace($config->get('app.url'), '', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

if($config->get('security.http.header.x.frame.options') != '')
	header('X-Frame-Options: '.$config->get('security.http.header.x.frame.options'));

// checking to see if the DB is installed before proceeding to process requests normally
$isInstalled = true;

if($config->get('app.check.installed') && !AlphaDAO::isInstalled())
	$isInstalled = false;


if(($request == '' || $request == '/') && $isInstalled) { // process requests to the root (/) URL here with your code

	echo AlphaView::loadTemplateFragment('html', 'head.phtml', array('title' => 'Alpha Framework', 'description' => 'Powered by the Alpha Framework'));

	echo <<<EOCSS
</head>
<body>
<div align="center">
<img src="{$config->get('app.url')}/alpha/images/logo-large.png"/>
<br>
<br>
<h3 style="color:black; font-size:200%;">Coming Soon!</h3>
</div>
EOCSS;

	echo AlphaView::loadTemplateFragment('html', 'footer.phtml', array());

}else{ // process requests through the FrontController
	try {
		$front = new FrontController();
		// register the article load by title alias
		$front->registerAlias('ViewArticleTitle','article','title');
		$front->registerAlias('Search','search','q');
		if($config->get('security.client.blacklist.filter.enabled')) {
			require_once $config->get('app.root').'alpha/util/filters/ClientBlacklistFilter.inc';
			$front->registerFilter(new ClientBlacklistFilter());
		}
		if($config->get('security.ip.blacklist.filter.enabled')) {
			require_once $config->get('app.root').'alpha/util/filters/IPBlacklistFilter.inc';
			$front->registerFilter(new IPBlacklistFilter());
		}
		if($config->get('security.client.temp.blacklist.filter.enabled')) {
			require_once $config->get('app.root').'alpha/util/filters/ClientTempBlacklistFilter.inc';
			$front->registerFilter(new ClientTempBlacklistFilter());
		}

		$front->loadController();
	}catch (LibraryNotInstalledException $lnie) {
		header('HTTP/1.1 404 Not Found');
		echo AlphaView::renderErrorPage(404, $lnie->getMessage());
	}catch (ResourceNotFoundException $rnfe) {
		header('HTTP/1.1 404 Not Found');
		echo AlphaView::renderErrorPage(404, $rnfe->getMessage());
	}catch (ResourceNotAllowedException $rnae) {
		header('HTTP/1.1 403 Forbidden');
		echo AlphaView::renderErrorPage(403, $rnae->getMessage());
	}
}

?>