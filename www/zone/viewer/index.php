<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'errorReporting' => false,
   'sessionStart'   => true,
   'memoryLimit'    => null,
   'sendHeaders'    => true,
   'dbConfigDir'    => APP_CONFIGDIR,
   'fileDefine'     => APP_CONFIGDIR.'/defines.json',
   'database'       => true,
   'input'          => false,
   'html'           => false,
   'adminlte'       => true,
   'data'           => APP_CONFIGDIR.'/global.json',
));

$main->title('Zone Viewer');
$main->pageDescription('Load zone maps to view terrain and spawn data');

include 'ui/header.php';

print "<style>.vcolumns { column-gap:20px; column-count:5; }</style>\n";

$alte = $main->obj('adminlte');

$currentExpansion = $main->data->forceExpansion('kunark') ?: $main->data->currentExpansion();
$expansionList    = $main->data->expansionList();

$zoneData = $main->data->getZones('short_name',array('short_name','long_name','canbind','cancombat','canlevitate','castoutdoor','expansion'));

$zoneList = array();

foreach ($zoneData as $keyId => $zoneInfo) {
   // Skip all instances and tryout versions, because they are the same zone
   if (preg_match('/(_tryout|_instanced|_alt)$/i',$zoneInfo['short_name']) || $zoneInfo['expansion'] == -1) { continue; }

   $zoneList[sprintf("%02d",$zoneInfo['expansion'])][$zoneInfo['long_name']] = $zoneInfo; 
}

ksort($zoneList);

foreach ($zoneList as $expansionNumber => $expansionZones) {
   $expansionName = $expansionList[sprintf("%d",$expansionNumber)]['info']['name'] ?: null;

   if (is_null($expansionName)) { continue; }

   $viewerData = array();

   $expansionColorClass = 'bg-primary';
 
   // We're looking up a zone that is outside of our current expansion, so we need to disregard spawn expansion locking
   if ($expansionNumber > $currentExpansion) { 
      $viewerData['ignoreXpn'] = 1; 
      $expansionColorClass = 'bg-secondary';
   }

   $viewerOpts = http_build_query($viewerData);

   ksort($expansionZones);

   $content = "<div class='vcolumns'>";
   foreach ($expansionZones as $zoneKey => $zoneInfo) {
      $content .= sprintf("<div><a class='text-warning' href='viewer.php?zone=%s&%s'>%s</a> (%s)</div>",$zoneInfo['short_name'],$viewerOpts,$zoneInfo['long_name'],$zoneInfo['short_name']);
   } 
   $content .= "</div>";

   print $alte->displayCard($alte->displayRow($content),array('container' => 'col-12', 'header' => $expansionColorClass, 'title' => $expansionName));
}

include 'ui/footer.php';