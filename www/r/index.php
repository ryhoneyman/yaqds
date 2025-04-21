<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main([
    'debugLevel'     => 0,
    'debugType'      => DEBUG_COMMENT,
    'debugBuffer'    => true,
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
if (!$main->buildClass('router','LWPLib\SimpleRouter',null,'simplerouter.class.php')) { exit; }

//$main->prepareDatabase('db.yaqds.conf','yaqds');

$router = $main->obj('router');

$router->process($main,[
    '/r/item/'  => ['function' => 'routeItem', 'method' => ['GET']],
    '/r/spell/' => ['function' => 'routeSpell', 'method' => ['GET']],
]);
 
?>
<?php

function routeSpell($main)
{
    $api    = $main->obj('api');
    $router = $main->obj('router');

    $requestUri = $router->http->requestUri();

    if (!preg_match('~/(\d+)$~',$requestUri,$match)) { $router->sendResponse(null,null,500,'html'); }

    $spellId   = $match[1];
    $spellData = $api->v1Spell($spellId);

    if (!$spellData || $spellData['error']) { $router->sendResponse($spellData['error'] ?: 'Spell not found',null,404,'html'); }

    //print json_encode($response,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

    print gameCard($requestUri,$spellData);
}

function routeItem($main)
{
    $api    = $main->obj('api');
    $router = $main->obj('router');

    $requestUri = $router->http->requestUri();

    if (!preg_match('~/(\d+)$~',$requestUri,$match)) { $router->sendResponse(null,null,500,'html'); }

    $itemId   = $match[1];
    $itemData = $api->v1Item($itemId);

    if (!$itemData || $itemData['error']) { $router->sendResponse($itemData['error'] ?: 'Item not found',null,404,'html'); }

    //print json_encode($response,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

    print gameCard($requestUri,$itemData);
}

function gameCard($cardLink, $cardData)
{
    $baseUrl = 'https://yaqds.cc';

    ksort($cardData);

    $values = [
        'NAME'        => sprintf("%s",$cardData['name']),
        'URL'         => sprintf("%s%s",$baseUrl,$cardLink),
        'TITLE'       => sprintf("%s",$cardData['name']),
        'DESCRIPTION' => htmlentities(implode("\n",$cardData['_description'])),
        'IMAGE'       => sprintf("%s/images/icons/item_%d.png",$baseUrl,$cardData['icon']),
        'DATA'        => json_encode($cardData,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),
    ];

    $values['BODY'] = "<div style='background:black; color:white; font-family:arial; font-size:15px; padding:5px; width:min-content;'>\n". 
                      "<div style='background:black; color:#bbb; font-family:arial; border:1px solid #333; padding:5px; width:auto; min-width:400px; text-align:center;'>".$values['NAME']."</div>\n". 
                      "<div style='padding:2px;'></div>\n".
                      "<div style='background:black; color:white; font-family:arial; border:1px solid #333; white-space:pre; padding:10px; width:auto; min-width:400px;'>".
                      "<img src='".$values['IMAGE']."' style='float:right; height:auto;'>".$values['DESCRIPTION']."</div>\n". 
                      "</div><p><pre>".$values['DATA']."</pre>";

    $template = "<html><head>\n". 
                "<meta property='og:url' content='{{URL}}'>\n".
                "<meta property='og:type' content='website'>\n".
                "<meta property='og:title' content='{{TITLE}}'>\n".
                "<meta property='og:description' content='{{DESCRIPTION}}'>\n".
                "<meta property='og:image' content='{{IMAGE}}'>\n". 
                "</head>\n".
                "<body>{{BODY}}</body>\n".
                "</html>";

    return replaceValues($template,$values);
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