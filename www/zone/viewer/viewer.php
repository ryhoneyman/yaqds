<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'errorReporting' => false,
   'sessionStart'   => true,
   'memoryLimit'    => null,
   'sendHeaders'    => true,
   'database'       => true,
   'input'          => true,
   'html'           => true,
   'adminlte'       => true,
   'constants'      => true,
   'data'           => true,
   'map'            => true,
));

$input = $main->obj('input');
$html  = $main->obj('html');

$currentExpansion = $main->constants->currentExpansion();

$zoneName  = $input->get('zone','alphanumeric') ?: 'hateplane';
$zoneFloor = $input->get('floor','numeric,dash');
$zoneCeil  = $input->get('ceil','numeric,dash');
$zoneLayer = $input->get('layer','alphanumeric');
$ignoreXpn = ($input->isDefined('ignoreXpn')) ? true : false;

$zoneInfo = $main->data->getZoneInfoByName($zoneName);

if (!$zoneInfo) { $main->redirect('/map/viewer/'); }

if ($ignoreXpn) { $currentExpansion = null; }

include 'ui/header.php';

print "<link rel='stylesheet' href='/assets/css/mapviewer.css?t={$main->now}'/>\n";
print "<script src='/assets/js/mapviewer.js?t={$main->now}'></script>\n";
print "<script src='/assets/js/svg-pan-zoom-container.js'></script>\n";

$svgDefs = array(
   "<marker id='head' orient='auto' markerWidth='3' markerHeight='4' refX='0.1' refY='2'> <path d='M0,0 V4 L2,2 Z' fill='black'/></marker>",
);

$zoneMapData = $main->constants->getZoneMapData($zoneName);
$zoneMapFile = $zoneInfo['map_file_name'] ?: $zoneMapData['file'] ?: $zoneName;

$layerSelect = array('all' => 'Everything');
$layerData   = $zoneMapData['layers'];

if ($layerData) {
   foreach ($layerData as $layerId => $layerInfo) { $layerSelect[$layerId] = $layerInfo['label']; }
   
   if ($zoneLayer) {
      $zoneFloor = $layerData[$zoneLayer]['floor']; 
      $zoneCeil  = $layerData[$zoneLayer]['ceil']; 
   }
}

$mapSVG      = $main->map->generateSVGMap($zoneMapFile,$zoneFloor,$zoneCeil,array('defs' => $svgDefs));
$spawnData   = $main->data->getMapSpawnInfoByZoneName($zoneName,$zoneFloor,$zoneCeil,$currentExpansion) ?: array();
$spawnGrids  = $main->data->getSpawnGridsByZoneName($zoneName) ?: array();
$spawnLabels = generateSpawnLabels($main,$spawnData,$spawnGrids);

// Order the labels for SVG last render on top
$svgLabels = array_merge($spawnLabels['headings'],$spawnLabels['spawns'],$spawnLabels['paths']);

// Add in our labels to the map SVG
array_splice($mapSVG,-1,0,$svgLabels);

$selectOpts = array('class' => 'form-control gear', 'script' => 'onchange="autoChange(this.value);"');

print "<div class='mb-1'>".
      "<div class='text-xl d-inline-block align-middle'><a class='mr-3' href='/zone/viewer/'><i class='fa fa-reply'></i></a> ".$zoneInfo['long_name']."</div>".
      "<div class='ml-3 d-inline-block align-middle'>".
      (($layerData) ? 
         $html->startForm().
         $html->select('layer',$layerSelect,$zoneLayer,$selectOpts).
         (($ignoreXpn) ? $html->inputHidden('ignoreXpn',$ignoreXpn) : '').
         $html->inputHidden('zone',$zoneName).
         $html->endForm() : '').
      "</div>".
      "</div>\n".
      "<div data-zoom-on-wheel data-pan-on-drag style='width:75vw; height:75vh; overflow-y:hidden; overflow-x:hidden; background:#ffffff;'>\n".
      implode("",$mapSVG)."\n".
      "</div>\n";

// Setup our coordinate and spawninfo boxes for hover on map
print "<div id='coord' style='font-family:monospace; background:#ffdddd; color:#000000; position:absolute; width:fit-content; display:flex;'></div>\n";
print "<div id='spawninfo' style='font-family:monospace; background:#ffffee; color:#000000; line-height:1; position:absolute; width:fit-content; display:flex; border:1px solid black;'></span>\n";

include 'ui/footer.php';

?>
<?php

function generateSpawnLabels($main, $spawnData, $spawnGrids, $options = null)
{
   $return = array('spawns' => array(), 'paths' => array(), 'headings' => array());
   
   $entityRadius = 5;
   $textSize     = 12;
   $arrowSize    = floor($entityRadius / 5) + 1;
   
   foreach ($spawnData as $keyId => $entryInfo) {
      $entityX = -$entryInfo['x'];
      $entityY = -$entryInfo['y'];
      $entityZ = $entryInfo['z'];
      $spawnXY = sprintf("%d_%d",$entityX,$entityY);
      $gridId  = $entryInfo['gridID'];
   
      $entityName = str_replace(array('#','_'),array('',' '),$entryInfo['name']);
   
      if ((!is_null($zoneFloor) && $entityZ < $zoneFloor) || (!is_null($zoneCeil) && $entityZ > $zoneCeil)) { continue; }
   
      $headingXY = $main->map->getXYFromHeading($entityRadius+2,$entryInfo['heading']);
   
      $arrowX1 = $entityX + $headingXY['x'];
      $arrowY1 = $entityY - $headingXY['y'];
      $arrowX2 = $entityX - $headingXY['x'];
      $arrowY2 = $entityY + $headingXY['y'];
   
      $spawns[$spawnXY]['pos'] = array(
         'x' => $entityX,
         'y' => $entityY,
         'ax1' => $arrowX1,
         'ay1' => $arrowY1,
         'ax2' => $arrowX2,
         'ay2' => $arrowY2,
      );
      $spawns[$spawnXY]['chance.total'] += $entryInfo['chance'];
      $spawns[$spawnXY]['spawn'][] = array('chance' => $entryInfo['chance'], 'name' => $entityName);
   
      if ($gridId) {
         $return['paths'][] = sprintf("<g data-grid='%s' style='visibility:hidden'>",$spawnXY);

         $startX = $entityX;
         $startY = $entityY;

         if (is_array($spawnGrids[$gridId])) { 
            foreach ($spawnGrids[$gridId] as $wpNumber => $wpInfo) {
               $waypointX = -$wpInfo['x'];
               $waypointY = -$wpInfo['y'];
   
               $return['paths'][] = sprintf("<path class='grid' d='M %d %d %d %d'/>\n",$startX,$startY,$waypointX,$waypointY);
        
               $startX = $waypointX;
               $startY = $waypointY;
            }
         }

         $return['paths'][] = "</g>";
      }
   }
   
   if (is_array($spawns)) {
      foreach ($spawns as $spawnXY => $spawnInfo) {
         $entityNames = array();

         if (is_array($spawnInfo['spawn'])) { 
            $chanceMult = ($spawnInfo['chance.total']) ? (100/$spawnInfo['chance.total']) : 1;
            foreach ($spawnInfo['spawn'] as $spawnEntry) { $entityNames[] = sprintf("%3d&#37; %s",$spawnEntry['chance'] * $chanceMult,$spawnEntry['name']); }
         }

         $entityList = implode('<br>',$entityNames);
         $spawnPos   = $spawnInfo['pos'];

         $return['headings'][] = sprintf("<path class='arrow' d='M %d %d %d %d'/>",$spawnPos['ax1'],$spawnPos['ay1'],$spawnPos['ax2'],$spawnPos['ay2']);
         $return['spawns'][]   = sprintf("<circle class='spawninfo' data-spawn='$spawnXY' data-spawninfo='$entityList' r='%d' cx='%d' cy='%d'></circle>\n",
                                         $entityRadius,$spawnPos['x'],$spawnPos['y']);
      }
   }

   return $return;
}

?>
