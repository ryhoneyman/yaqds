<?php
include_once 'yaqds-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'debugType'      => DEBUG_HTML,
   'errorReporting' => false,
   'sessionStart'   => true,
   'memoryLimit'    => null,
   'sendHeaders'    => true,
   'dbConfigDir'    => APP_CONFIGDIR,
   'fileDefine'     => APP_CONFIGDIR.'/defines.json',
   'database'       => true,
   'input'          => true,
   'html'           => true,
   'adminlte'       => false,
   'data'           => APP_CONFIGDIR.'/global.json',
   'map'            => true,
));

$input = $main->obj('input');
$html  = $main->obj('html');

$currentExpansion = $main->data->forceExpansion() ?: $main->data->currentExpansion();

$zoneName    = $input->get('zone','alphanumeric') ?: 'hateplane';
$zoneFloor   = $input->get('floor','numeric,dash');
$zoneCeil    = $input->get('ceil','numeric,dash');
$zoneLayer   = $input->get('layer','alphanumeric');
$zonePathing = $input->get('pathing','alphanumeric') ?: 'enabled';
$npcSearch   = $input->get('search','all') ?: null;
$ignoreXpn   = ($input->isDefined('ignoreXpn')) ? true : false;

$zoneData = $main->data->getZones('short_name',array('short_name','long_name','expansion'));
$zoneInfo = $main->data->getZoneInfoByName($zoneName);

if (!$zoneInfo) { $main->redirect('/map/viewer/'); }

if ($ignoreXpn) { $currentExpansion = null; }

include 'ui/header.php';

print "<link rel='stylesheet' href='/assets/css/mapviewer.css?t={$main->now}'/>\n";
print "<script src='/assets/js/mapviewer.js?t={$main->now}'></script>\n";
print "<script src='/assets/js/svg-pan-zoom-container.js'></script>\n";

print "<style>\n".
      ".select2-results__option { line-height:1.0; }\n".
      ".select2-container--default .select2-results>.select2-results__options { max-height: 350px; }\n".
      "</style>\n";

$svgDefs = array(
   "<marker id='head' orient='auto' markerWidth='3' markerHeight='4' refX='0.1' refY='2'><path d='M0,0 V4 L2,2 Z' fill='black'/></marker>",
   "<path id='crosshair' d='M30 14.75h-2.824c-0.608-5.219-4.707-9.318-9.874-9.921l-0.053-0.005v-2.824c0-0.69-0.56-1.25-1.25-1.25s-1.25 0.56-1.25 1.25v0 2.824c-5.219 0.608-9.318 4.707-9.921 9.874l-0.005 0.053h-2.824c-0.69 0-1.25 0.56-1.25 1.25s0.56 1.25 1.25 1.25v0h2.824c0.608 5.219 4.707 9.318 9.874 9.921l0.053 0.005v2.824c0 0.69 0.56 1.25 1.25 1.25s1.25-0.56 1.25-1.25v0-2.824c5.219-0.608 9.318-4.707 9.921-9.874l0.005-0.053h2.824c0.69 0 1.25-0.56 1.25-1.25s-0.56-1.25-1.25-1.25v0zM17.25 24.624v-2.624c0-0.69-0.56-1.25-1.25-1.25s-1.25 0.56-1.25 1.25v0 2.624c-3.821-0.57-6.803-3.553-7.368-7.326l-0.006-0.048h2.624c0.69 0 1.25-0.56 1.25-1.25s-0.56-1.25-1.25-1.25v0h-2.624c0.57-3.821 3.553-6.804 7.326-7.368l0.048-0.006v2.624c0 0.69 0.56 1.25 1.25 1.25s1.25-0.56 1.25-1.25v0-2.624c3.821 0.57 6.803 3.553 7.368 7.326l0.006 0.048h-2.624c-0.69 0-1.25 0.56-1.25 1.25s0.56 1.25 1.25 1.25v0h2.624c-0.571 3.821-3.553 6.803-7.326 7.368l-0.048 0.006z'/>",
);

$zoneMapData = $main->data->getZoneMapData($zoneName);
$zoneMapFile = $zoneInfo['map_file_name'] ?: $zoneMapData['file'] ?: $zoneName;

// Build zone selector
$zoneSelect = [];
foreach ($zoneData as $zoneDataKey => $zoneDataInfo) { 
   // Skip all instances and tryout versions, because they are the same zone
   if (preg_match('/(_tryout|_instanced)$/i',$zoneDataInfo['short_name'])) { continue; }
   if ($currentExpansion && $currentExpansion < $zoneDataInfo['expansion']) { continue; } 

   $zoneSelect[$zoneDataInfo['short_name']] = $zoneDataInfo['long_name']; 
}
asort($zoneSelect);

// Build zone layer selector
$layerSelect = array('all' => 'Everything');
$layerData   = $zoneMapData['layers'];

if ($layerData) {
   foreach ($layerData as $layerId => $layerInfo) { $layerSelect[$layerId] = $layerInfo['label']; }
   
   if ($zoneLayer) {
      $zoneFloor = $layerData[$zoneLayer]['floor']; 
      $zoneCeil  = $layerData[$zoneLayer]['ceil']; 
   }
}

$mapSVG       = $main->map->generateSVGMap($zoneMapFile,$zoneFloor,$zoneCeil,array('defs' => $svgDefs));
$spawnData    = $main->data->getMapSpawnInfoByZoneName($zoneName,$zoneFloor,$zoneCeil,$currentExpansion) ?: array();
$spawnGrids   = (preg_match('/^enable/i',$zonePathing)) ? $main->data->getSpawnGridsByZoneName($zoneName) ?: array() : array();
$mapWidth     = $main->map->svgWidth;
$mapHeight    = $main->map->svgHeight;
$mapPresets   = getMapPresets($mapWidth,$mapHeight);
$entityRadius = $mapPresets['entityRadius'];
$targetScale  = $mapPresets['targetScale'];
$arrowSize    = floor($entityRadius / 2);
$gridSize     = floor($entityRadius / 1.25); 
$spawnLabels  = generateSpawnLabels($main,$spawnData,$spawnGrids,array('search' => $npcSearch, 'entityRadius' => $entityRadius));

//print "<pre class='text-black'>".json_encode($spawnGrids,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."</pre>";
//printf("radius(%s) arrow(%s) grid(%s)<br>\n",$entityRadius,$arrowSize,$gridSize);

$npcSelect = ['' => ''];
foreach (array_keys($spawnLabels['mobs']['by-name']) as $npcName) {
   $npcSelect[sprintf('nn%s',hash('crc32',$npcName))] = $npcName;
}

// Dynamic styles
print "<style>\n". 
      ".gridS  { stroke-width: $gridSize; stroke-dasharray: $gridSize; }\n".
      ".arrowS { stroke-width: $arrowSize; }\n".
      "@keyframes spin { from { transform: scale($targetScale) rotate(0deg); } to { transform: scale($targetScale) rotate(359deg); } }\n".
      "</style>\n";

// Order the labels for SVG last render on top
$svgLabels = array_merge($spawnLabels['headings'],$spawnLabels['spawns'],$spawnLabels['paths'],$spawnLabels['search']);

// Add in our labels to the map SVG
array_splice($mapSVG,-1,0,$svgLabels);

$selectOpts = array('class' => 'form-control gear', 'script' => 'onchange="autoChange(this.value);"');

print "<div class='mb-1'>".
      //"<div class='text-xl d-inline-block align-middle'><a class='mr-3' href='/zone/viewer/'><i class='fa fa-reply'></i></a> ".$zoneInfo['long_name']."</div>".
      $html->startForm().
      "<div class='d-inline-block align-top' style='width:fit-content;'>".
      $html->select('zone',$zoneSelect,$zoneName,$selectOpts).
      "</div>".
      "<div class='d-inline-block align-top'>".
      (($layerData) ?  $html->select('layer',$layerSelect,$zoneLayer,$selectOpts) : '').
      "</div>".
      "<div class='d-inline-block align-top'style='width:fit-content;'>".
      $html->select('search',$npcSelect,$npcSearch,$selectOpts).
      "</div>".
      //"<div class='d-inline-block align-top'>".
      //$html->select('pathing',array('enabled' => 'Pathing Enabled (slower)', 'disabled' => 'Pathing Disabled'),$zonePathing,$selectOpts).
      //"</div>".
      (($ignoreXpn) ? $html->inputHidden('ignoreXpn',$ignoreXpn) : '').
      $html->endForm().
      "</div>\n".
      "<div data-zoom-on-wheel data-pan-on-drag style='width:75vw; height:75vh; overflow-y:hidden; overflow-x:hidden; background:#ffffff;'>\n".
      implode("",$mapSVG)."\n".
      "</div>\n";

// Setup our coordinate and spawninfo boxes for hover on map
print "<div id='coord' style='font-family:monospace; background:#ffdddd; color:#000000; position:absolute; width:fit-content; display:flex;'></div>\n";
print "<div id='spawninfo' style='font-family:monospace; background:#ffffee; color:#000000; line-height:1; position:absolute; width:fit-content; display:flex; border:1px solid black;'></span>\n";

print "<script type='text/javascript'>\n".
      "   $('#zone').select2();\n".
      "   $('#search').select2();\n".
      "</script>\n";

include 'ui/footer.php';

?>
<?php

function generateSpawnLabels($main, $spawnData, $spawnGrids, $options = null)
{
   $return = array('spawns' => array(), 'paths' => array(), 'headings' => array(), 'search' => array());

   $entityRadius = $options['entityRadius'];
   $searchHash   = $options['search'] ?: null;

   $spawns = array();
   $grids  = array();
   
   foreach ($spawnData as $keyId => $entryInfo) {
      $entityX    = $entryInfo['x'];
      $entityY    = $entryInfo['y'];
      $entityZ    = $entryInfo['z'];
      $spawnXY    = sprintf("%d_%d",$entryInfo['y'],$entryInfo['x']);
      $entityMapX = -$entityX;  // EQ maps are inverted axis
      $entityMapY = -$entityY;  // EQ maps are inverted axis
      $entityLvl  = $entryInfo['level'];
      $groupId    = $entryInfo['sgID'];
      $gridId    = $entryInfo['gridID'];
   
      $entityName = str_replace(array('#','_'),array('',' '),$entryInfo['name']);
      $entityId   = $entryInfo['npcID'];
   
      if ((!is_null($zoneFloor) && $entityZ < $zoneFloor) || (!is_null($zoneCeil) && $entityZ > $zoneCeil)) { continue; }
   
      $spawns[$spawnXY][$groupId]['chance.total'] += $entryInfo['chance'];

      $headingXY = $main->map->getXYFromHeading($entityRadius+2,$entryInfo['heading']);
   
      $arrowX1 = $entityMapX + $headingXY['x'];
      $arrowY1 = $entityMapY - $headingXY['y'];
      $arrowX2 = $entityMapX - $headingXY['x'];
      $arrowY2 = $entityMapY + $headingXY['y'];

      $spawns[$spawnXY][$groupId]['pos'] = array(
         'x' => $entityMapX,
         'y' => $entityMapY,
         'ax1' => $arrowX1,
         'ay1' => $arrowY1,
         'ax2' => $arrowX2,
         'ay2' => $arrowY2,
      );

      $npcMatch = false;

      if ($searchHash && $searchHash == sprintf('nn%s',hash('crc32',$entityName))) {
         $npcMatch   = true;
         $originMapX = $entityMapX + 1;
         $originMapY = $entityMapY + 1;
         $return['search'][] = sprintf("<use class='crosshair' transform-origin='$originMapX $originMapY' x=%s y=%s href='#crosshair'/>\n",$entityMapX-15,$entityMapY-15); 
      }
      $spawns[$spawnXY][$groupId]['info']['sgName'] = $entryInfo['sgName'];
      $spawns[$spawnXY][$groupId]['spawn'][] = array('chance' => $entryInfo['chance'], 'id' => $entityId, 'name' => $entityName, 'level' => $entityLvl, 'match' => $npcMatch);

      $return['mobs']['by-name'][$entityName][$entityId]++;
      $return['mobs']['by-id'][$entityId][$entityName]++;
   
      if ($gridId && is_array($spawnGrids[$gridId])) {
         if (!$grids[$gridId]) {
            $grids[$gridId] = array(sprintf("<g data-grid='%s' style='visibility:hidden'>",$gridId));


            $startX = $entityMapX;
            $startY = $entityMapY;

            foreach ($spawnGrids[$gridId] as $wpNumber => $wpInfo) {
               $waypointX = -$wpInfo['x'];  // EQ waypoints are inverted axis
               $waypointY = -$wpInfo['y'];  // EQ waypoints are inverted axis
   
               $grids[$gridId][] = sprintf("<path class='grid gridS' d='M %d %d %d %d'/>",$startX,$startY,$waypointX,$waypointY);
       
               $startX = $waypointX;
               $startY = $waypointY;
            }
            $grids[$gridId][] = "</g>\n";
         }

         $spawns[$spawnXY][$groupId]['info']['grid']   = $gridId;
         $spawns[$spawnXY][$groupId]['info']['pathId'] = $gridId;
      }
      else if ($entryInfo['sgMinX'] || $entryInfo['sgMinY'] || $entryInfo['sgMaxX'] || $entryInfo['sgMaxY']) {
         $width    = abs($entryInfo['sgMaxY'] - $entryInfo['sgMinY']);
         $height   = abs($entryInfo['sgMaxX'] - $entryInfo['sgMinX']);
         $topLeftX = -$entryInfo['sgMaxX'];  // EQ waypoints are inverted axis
         $topLeftY = -$entryInfo['sgMaxY'];  // EQ waypoints are inverted axis
         $roamId   = 'sg'.$groupId;
         
         if (!isset($grids[$roamId])) {
            $grids[$roamId] = [
               sprintf("<g data-grid='%s' style='visibility:hidden'>",$roamId),
               sprintf("<rect class='grid gridS' x=%d y=%d width=%d height=%d stroke='black' fill='transparent'/>",$topLeftX,$topLeftY,$height,$width),
               sprintf("</g>\n"),
            ];
         }

         $spawns[$spawnXY][$groupId]['info']['roambox'] = sprintf("%s,%s,%s,%s",$entryInfo['sgMaxY'],$entryInfo['sgMaxX'],$entryInfo['sgMinY'],$entryInfo['sgMinX']);
         $spawns[$spawnXY][$groupId]['info']['pathId']  = $roamId;
      }
   }

   if (is_array($grids)) {
      foreach ($grids as $gridId => $gridSVG) { 
         $return['paths'][] = implode('',$gridSVG);
      }
   }
   
   if (is_array($spawns)) {
      foreach ($spawns as $spawnXY => $spawnGroups) {
         $groupData = [];
         $spawnPos  = null;
         $spawnPath = null;

         foreach ($spawnGroups as $groupId => $spawnInfo) {
            $spawnMeta      = $spawnInfo['info'];
            $spawnGroupName = $spawnMeta['sgName'];
            $spawnGrid      = $spawnMeta['grid'];
            $spawnRoambox   = $spawnMeta['roambox'];
            $entityNames    = array();

            if (is_array($spawnInfo['spawn'])) { 
               $chanceMult = ($spawnInfo['chance.total']) ? (100/$spawnInfo['chance.total']) : 1;
               foreach ($spawnInfo['spawn'] as $spawnEntry) { 
                  $spawnMatch = $spawnEntry['match'];

                  $entityEntry = sprintf("%3d&#37; %s (L%s)",$spawnEntry['chance'] * $chanceMult,$spawnEntry['name'],$spawnEntry['level']);
            
                  if ($spawnMatch) { $entityEntry = sprintf("<span class='text-bold text-red'>%s</span>",$entityEntry); }

                  $entityNames[] = $entityEntry; 
               }
            }

            $spawnPos  = $spawnInfo['pos'];
            $spawnPath = $spawnMeta['pathId'];

            $groupData[$groupId]['name']   = $spawnGroupName;
            $groupData[$groupId]['roam']   = ($spawnGrid) ? 'Path Grid '.$spawnGrid : (($spawnRoambox) ? 'Roambox '.$spawnRoambox : 'Static');
            $groupData[$groupId]['spawns'] = $entityNames;

            //printf("%s: spawnXY(%s) name(%s) grid(%s) roambox(%s)<br>\n",$groupId,$spawnXY,$groupData[$groupId]['name'],$spawnInfo['info']['grid'],$spawnInfo['info']['roambox']);
         }

         $spawnJson = [];

         foreach ($groupData as $groupId => $groupInfo) {
            $spawnJson[] = [
               'groupName'      => $groupInfo['name'], 
               'groupId'        => $groupId, 
               'groupSpawnList' => $groupInfo['spawns'], 
               'roamType'       => $groupInfo['roam']
            ];
         }

         $spawnJsonEncoded = base64_encode(json_encode($spawnJson,JSON_UNESCAPED_SLASHES));

         $return['headings'][] = sprintf("<path class='arrow arrowS' d='M %d %d %d %d'/>",$spawnPos['ax1'],$spawnPos['ay1'],$spawnPos['ax2'],$spawnPos['ay2']);
         $return['spawns'][]   = sprintf("<circle class='spawninfo' data-spawn='$spawnXY' data-pathing='$spawnPath' data-spawninfo=\"$spawnJsonEncoded\" r='%d' cx='%d' cy='%d'></circle>\n",
                                         $entityRadius,$spawnPos['x'],$spawnPos['y']);
      }
   }

   return $return;
}

function getMapPresets($mapWidth, $mapHeight)
{
   $entityRadius = 15;
   $targetScale  = 4;

   if ($mapWidth < 1000)      { $entityRadius = 3; $targetScale = 1; }
   else if ($mapWidth < 3000) { $entityRadius = 5; $targetScale = 2; }
   else if ($mapWidth < 5000) { $entityRadius = 10; $targetScale = 3; }

   return ['entityRadius' => $entityRadius, 'targetScale' => $targetScale];
}
?>
