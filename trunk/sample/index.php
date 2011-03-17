<?php

// include the config file
require_once 'alpha/util/AlphaConfig.inc';
$config = AlphaConfig::getInstance();

require_once $config->get('sysRoot').'alpha/model/AlphaDAO.inc';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';
require_once $config->get('sysRoot').'alpha/controller/ViewArticleTitle.php';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

$request = str_replace($config->get('sysURL'), '', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

// checking to see if the DB is installed before proceeding to process requests normally
$isInstalled = true;

if($config->get('sysCheckInstalled')&& !AlphaDAO::isInstalled())
	$isInstalled = false;

if($request == '' && $isInstalled) { // process requests to the root (/) URL here with your code
	echo <<<EOCSS
<html>
<head>

<title>Alpha Framework</title>

<link rel="StyleSheet" type="text/css" href="{$config->get('sysURL')}alpha/css/alpha.css">
<link rel="StyleSheet" type="text/css" href="{$config->get('sysURL')}config/css/overrides.css">
		
</head>
<body>
<div align="center">
<img src="{$config->get('sysURL')}/alpha/images/logo-large.png"/>
<br>
<br>
<br>
<br>
<h3 style="color:black; font-size:200%;">Coming Soon!</h3>
</div>
</body>
</html>
EOCSS;
}else{ // process requests through the FrontController
	try {
		$front = new FrontController();
		// register the article load by title alias
		$front->registerAlias('ViewArticleTitle','article','title');
		$front->registerAlias('Search','search','q');
		if($config->get('sysEnableClientBlacklistFilter')) {		
			require_once $config->get('sysRoot').'alpha/util/filters/ClientBlacklistFilter.inc';
			$front->registerFilter(new ClientBlacklistFilter());
		}
		if($config->get('sysEnableClientTempBlacklistFiler')) {		
			require_once $config->get('sysRoot').'alpha/util/filters/ClientTempBlacklistFilter.inc';
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