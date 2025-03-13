<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main([
    'debugLevel'     => 0,
    'debugType'      => DEBUG_COMMENT,
    'debugBuffer'    => false,
    'debugLogDir'    => APP_LOGDIR,
    'errorReporting' => false, 
    'sessionStart'   => true,
    'memoryLimit'    => null,
    'sendHeaders'    => true,
    'database'       => false,
    'dbConfigDir'    => APP_CONFIGDIR,
    'fileDefines'    => null,
    'dbDefines'      => null,
    'input'          => false,
    'html'           => false,
    'adminlte'       => false,
]);

$settings   = json_decode(file_get_contents(APP_CONFIGDIR.'/settings.json'),true);
$apiOptions = ['baseUrl' => $settings['YAQDS_API_URL'], 'authToken' => $settings['YAQDS_API_AUTH_TOKEN']];

if (!$main->buildClass('api','MyAPI',$apiOptions,'local/myapi.class.php')) { exit; }
if (!$main->buildClass('restapi','LWPLib\RESTAPI',null,'restapi.class.php')) { exit; }

//$main->prepareDatabase('db.yaqds.conf','yaqds');

$restapi = $main->obj('restapi');

$restapi->router($main,[
    '/r/item/' => ['function' => 'routeItem', 'method' => ['GET']],
]);
 
?>
<?php

function routeItem($main)
{
    $api     = $main->obj('api');
    $restapi = $main->obj('restapi');

    $requestUri = $restapi->requestUri();

    if (!preg_match('~/(\d+)$~',$requestUri,$match)) { $restapi->sendResponse(null,null,500,'html'); }

    $itemId   = $match[1];
    $response = $api->v1Item($itemId);

    if (!$response || $response['error']) { $restapi->sendResponse($response['error'] ?: null,null,404,'html'); }

    print json_encode($response,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
}

?>