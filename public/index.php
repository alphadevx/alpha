<?php

require_once __DIR__.'/../vendor/autoload.php';

use Alpha\Controller\Front\FrontController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Filter\ClientBlacklistFilter;
use Alpha\Util\Http\Filter\IPBlacklistFilter;
use Alpha\Util\Http\Filter\ClientTempBlacklistFilter;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\View\View;

try {
    $config = ConfigProvider::getInstance();

    set_exception_handler('Alpha\Util\ErrorHandlers::catchException');
    set_error_handler('Alpha\Util\ErrorHandlers::catchError', $config->get('php.error.log.level'));

    $front = new FrontController();
	
    if ($config->get('security.client.blacklist.filter.enabled')) {
        $front->registerFilter(new ClientBlacklistFilter());
    }

    if ($config->get('security.ip.blacklist.filter.enabled')) {
        $front->registerFilter(new IPBlacklistFilter());
    }

    if ($config->get('security.client.temp.blacklist.filter.enabled')) {
        $front->registerFilter(new ClientTempBlacklistFilter());
    }

    $request = new Request();
    $response = $front->process($request);

} catch (ResourceNotFoundException $rnfe) {
    $response = new Response(404, View::renderErrorPage(404, $rnfe->getMessage(), array('Content-Type' => 'text/html')));
} catch (ResourceNotAllowedException $rnae) {
    $response = new Response(403, View::renderErrorPage(403, $rnae->getMessage(), array('Content-Type' => 'text/html')));
}

if ($config->get('security.http.header.x.frame.options') != '' && $response->getHeader('X-Frame-Options') == null) {
    $response->setHeader('X-Frame-Options', $config->get('security.http.header.x.frame.options'));
}

echo $response->send();

?>