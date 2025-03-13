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

    //print json_encode($response,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

    $baseUrl = 'https://yaqds.cc';

    $values = [
        'URL'         => sprintf("%s%s",$baseUrl,$requestUri),
        'TITLE'       => sprintf("%s | Item | YAQDS",$response['name']),
        'DESCRIPTION' => implode("\n",$response['_description']),
        'IMAGE'       => sprintf("%s/images/icons/item_%d.png",$baseUrl,$response['icon']),
    ];

    $template = "<html><head>\n". 
                "<meta property='og:url' content='{{URL}}'>\n".
                "<meta property='og:type' content='website'>\n".
                "<meta property='og:title' content='{{TITLE}}'>\n".
                "<meta property='og:description' content='{{DESCRIPTION}}'>\n".
                "<meta property='og:image' content='{{IMAGE}}'>\n". 
                "</head></html>";

    print replaceValues($template,$values);
}

function replaceValues($string, $values)
{
    if (!is_null($values) && is_array($values)) {
        $replace = array();
        foreach ($values as $key => $value) { $replace['{{'.$key.'}}'] = ((is_array($value)) ? implode('|',array_filter(array_unique($value))) : ((is_bool($value)) ? json_encode($value) : $value)); }

        $string = str_replace(array_keys($replace),array_values($replace),$string);
    }

    return $string;
}

?>