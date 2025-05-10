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
    'fileDefine'     => APP_CONFIGDIR.'/settings.json',
    'dbDefine'       => null,
    'input'          => true,
    'html'           => false,
    'adminlte'       => false,
]);

$apiOptions = ['baseUrl' => MY_API_URL, 'authToken' => MY_API_AUTH_TOKEN];

if (!$main->buildClass('api','MyAPI',$apiOptions,'local/myapi.class.php')) { exit; }
if (!$main->buildClass('router','LWPLib\SimpleRouter',null,'simplerouter.class.php')) { exit; }

//$main->prepareDatabase('db.yaqds.conf','yaqds');

$router = $main->obj('router');

$router->process($main,[
    '/r/item/'       => ['function' => 'routeItem', 'method' => ['GET']],
    '/r/spell/'      => ['function' => 'routeSpell', 'method' => ['GET']],
    '/r/data/spell' => ['function' => 'routeSpellData', 'method' => ['GET']],
]);
 
?>
<?php

function routeSpellData($main)
{
    $api    = $main->obj('api');
    $router = $main->obj('router');
    $input  = $main->obj('input');

    $headless   = preg_match('~application/json~i',$_SERVER['HTTP_ACCEPT']) ? true : false;
    $zealFormat = preg_match('/^(1|yes|true)$/i',$input->get('zeal','alphanumeric')) ? true : false;

    $spellData = $api->v1SpellData();

    if (!$spellData || $spellData['error']) { $router->sendResponse($spellData['error'] ?: 'Could not load spell data',null,400,'html'); }

    if (!$headless) { print "<pre>\n"; }

    if ($zealFormat) {
        $spellText = [];
        for ($spellId = 1; $spellId <= 4000; $spellId++) {
            if (!isset($spellData[$spellId]['data'])) { $spellText[] = ''; }

            $spellText[] = implode('^',$spellData[$spellId]['data'] ?? []);
        }

        print implode("\n",$spellText);
    }
    else {
        print json_encode($spellData,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }

    if (!$headless) { print "</pre>\n"; }
}

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

    $baseUrl   = MY_URL;
    $spellType = ($spellData['_is_bard_song']) ? 'Song' : 'Spell';

    print gameCard($spellData,[
        'name'      => sprintf("%s: %s",$spellType,$spellData['name']),
        'url'       => sprintf("%s%s",$baseUrl,$requestUri),
        'iconImage' => sprintf("%s/images/icons/%d.gif",$baseUrl,$spellData['custom_icon']),
    ]);
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

    $baseUrl = MY_URL;

    print gameCard($itemData,[
        'name'      => sprintf("%s",$itemData['name']),
        'url'       => sprintf("%s%s",$baseUrl,$requestUri),
        'iconImage' => sprintf("%s/images/icons/item_%d.png",$baseUrl,$itemData['icon']),
    ]);
}

function gameCard($cardData, $data = null)
{
    ksort($cardData);

    $values = [
        'NAME'        => htmlentities($data['name']),
        'URL'         => $data['url'],
        'TITLE'       => htmlentities($data['name']),
        'DESCRIPTION' => htmlentities(implode("\n",$cardData['_description'] ?? [])),
        'IMAGE'       => $data['iconImage'] ?: '',
        'DATA'        => json_encode($cardData,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),
    ];

    $bodyTemplate = "<div style='background:black; color:white; font-family:arial; font-size:15px; padding:5px; width:min-content;'>\n". 
                    "<div style='background:black; color:#bbb; font-family:arial; border:1px solid #333; padding:5px; width:auto; min-width:400px; text-align:center;'>{{NAME}}</div>\n". 
                    "<div style='padding:2px;'></div>\n".
                    "<div style='background:black; color:white; font-family:arial; border:1px solid #333; white-space:pre; padding:10px; width:auto; min-width:400px;'>".
                    "<img src='{{IMAGE}}' style='float:right; height:auto;'>{{DESCRIPTION}}</div>\n". 
                    "</div><p><pre>{{DATA}}</pre>";

    $values['BODY'] = replaceValues($bodyTemplate,$values);

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